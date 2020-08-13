<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Error;

use Exception;
use GraphQL\Error\Error as GraphQLError;
use GraphQL\Error\UserError as GraphQLUserError;
use GraphQL\Executor\ExecutionResult;
use InvalidArgumentException;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Error\ExceptionConverter;
use Overblog\GraphQLBundle\Error\ExceptionConverterInterface;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Error\UserErrors;
use Overblog\GraphQLBundle\Error\UserWarning;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function is_array;
use function is_string;
use function sprintf;

final class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $errorHandler;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $exceptionConverter = $this->createMock(ExceptionConverterInterface::class);
        $this->errorHandler = new ErrorHandler($this->dispatcher, $exceptionConverter);

        $this->dispatcher->expects($this->any())
            ->method('dispatch')
            ->willReturnArgument(0);

        $exceptionConverter->expects($this->any())
            ->method('convertException')
            ->willReturnArgument(0);
    }

    public function testMaskErrorWithThrowExceptionSetToFalse(): void
    {
        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError('Error without wrapped exception'),
                new GraphQLError('Error with wrapped exception', null, null, [], null, new Exception('My Exception message')),
                new GraphQLError('Error with wrapped user error', null, null, [], null, new UserError('My User Error')),
                new GraphQLError('Error with wrapped base user error', null, null, [], null, new GraphQLUserError('My bases User Error')),
                new GraphQLError('', null, null, [], null, new UserErrors(['My User Error 1', 'My User Error 2', new UserError('My User Error 3')])),
                new GraphQLError('Error with wrapped user warning', null, null, [], null, new UserWarning('My User Warning')),
            ]
        );

        $this->errorHandler->handleErrors($executionResult);

        $expected = [
            'errors' => [
                [
                    'message' => 'Error without wrapped exception',
                    'extensions' => ['category' => 'graphql'],
                ],
                [
                    'message' => ErrorHandler::DEFAULT_ERROR_MESSAGE,
                    'extensions' => ['category' => 'internal'],
                ],
                [
                    'message' => 'Error with wrapped user error',
                    'extensions' => ['category' => 'user'],
                ],
                [
                    'message' => 'Error with wrapped base user error',
                    'extensions' => ['category' => 'user'],
                ],
                [
                    'message' => 'My User Error 1',
                    'extensions' => ['category' => 'user'],
                ],
                [
                    'message' => 'My User Error 2',
                    'extensions' => ['category' => 'user'],
                ],
                [
                    'message' => 'My User Error 3',
                    'extensions' => ['category' => 'user'],
                ],
            ],
            'extensions' => [
                'warnings' => [
                    [
                        'message' => 'Error with wrapped user warning',
                        'extensions' => ['category' => 'user'],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $executionResult->toArray());
    }

    public function testMaskErrorWithWrappedExceptionAndThrowExceptionSetToTrue(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('My Exception message');

        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError('Error with wrapped exception', null, null, [], null, new Exception('My Exception message')),
            ]
        );

        $this->errorHandler->handleErrors($executionResult, true);
    }

    public function testInvalidUserErrorsItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Error must be string or instance of %s', GraphQLUserError::class));

        new UserErrors([
            'Some Error',
            false,
        ]);
    }

    public function testMaskErrorWithWrappedUserErrorAndThrowExceptionSetToTrue(): void
    {
        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError('Error with wrapped user error', null, null, [], null, new UserError('My User Error')),
            ]
        );

        $this->errorHandler->handleErrors($executionResult, true);

        $expected = [
            'errors' => [
                [
                    'message' => 'Error with wrapped user error',
                    'extensions' => ['category' => 'user'],
                ],
            ],
        ];

        $this->assertSame($expected, $executionResult->toArray());
    }

    public function testDebugEnabled(): void
    {
        try {
            throw new Exception();
        } catch (Exception $exception) {
            $executionResult = new ExecutionResult(
                null,
                [
                    new GraphQLError('Error wrapped exception', null, null, [], null, $exception),
                ]
            );

            $this->errorHandler->handleErrors($executionResult, false, true);

            $errors = $executionResult->toArray()['errors'];
            $this->assertCount(1, $errors);
            $this->assertArrayHasKey('debugMessage', $errors[0]);
            $this->assertSame('Error wrapped exception', $errors[0]['debugMessage']);
            $this->assertSame(ErrorHandler::DEFAULT_ERROR_MESSAGE, $errors[0]['message']);
            $this->assertArrayHasKey('trace', $errors[0]);
        }
    }

    public function testMaskErrorWithoutWrappedExceptionAndThrowExceptionSetToTrue(): void
    {
        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError('Error without wrapped exception'),
            ]
        );

        $this->errorHandler->handleErrors($executionResult, true);

        $expected = [
            'errors' => [
                [
                    'message' => 'Error without wrapped exception',
                    'extensions' => ['category' => 'graphql'],
                ],
            ],
        ];

        $this->assertSame($expected, $executionResult->toArray());
    }

    public function testConvertExceptionToUserWarning(): void
    {
        $exceptionConverter = new ExceptionConverter([InvalidArgumentException::class => UserWarning::class]);
        $errorHandler = new ErrorHandler($this->dispatcher, $exceptionConverter);
        $executionResult = new ExecutionResult(null, [
            new GraphQLError(
                'Error with invalid argument exception',
                null,
                null,
                [],
                null,
                new InvalidArgumentException('Invalid argument exception')
            ),
        ]);

        $errorHandler->handleErrors($executionResult, true);

        $expected = [
            'extensions' => [
                'warnings' => [
                    [
                        'message' => 'Error with invalid argument exception',
                        'extensions' => ['category' => 'user'],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $executionResult->toArray());
    }

    /**
     * @param bool         $mapExceptionsToParent
     * @param array|string $expectedUserError
     *
     * @dataProvider parentExceptionMappingDataProvider
     */
    public function testConvertExceptionUsingParentExceptionMatchesAlwaysFirstExactExceptionOtherwiseMatchesParent(array $exceptionMap, $mapExceptionsToParent, $expectedUserError): void
    {
        $exceptionConverter = new ExceptionConverter($exceptionMap, $mapExceptionsToParent);
        $errorHandler = new ErrorHandler($this->dispatcher, $exceptionConverter);
        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError(
                    'Error with invalid argument exception',
                    null,
                    null,
                    [],
                    null,
                    new ChildOfInvalidArgumentException('Invalid argument exception')
                ),
            ]
        );

        if (is_string($expectedUserError)) {
            self::expectException($expectedUserError); // @phpstan-ignore-line
        }
        $errorHandler->handleErrors($executionResult, true);

        if (is_array($expectedUserError)) {
            $this->assertSame($expectedUserError, $executionResult->toArray());
        }
    }

    /**
     * @return array
     */
    public function parentExceptionMappingDataProvider()
    {
        return [
            'without $mapExceptionsToParent and only the exact class, maps to exact class' => [
                [
                    ChildOfInvalidArgumentException::class => UserError::class,
                ],
                false,
                [
                    'errors' => [
                        [
                            'message' => 'Error with invalid argument exception',
                            'extensions' => ['category' => 'user'],
                        ],
                    ],
                ],
            ],
            'without $mapExceptionsToParent and only the parent class, does not map to parent' => [
                [
                    InvalidArgumentException::class => UserWarning::class,
                ],
                false,
                ChildOfInvalidArgumentException::class,
            ],
            'with $mapExceptionsToParent and no classes' => [
                [],
                true,
                ChildOfInvalidArgumentException::class,
            ],
            'with $mapExceptionsToParent and only the exact class' => [
                [
                    ChildOfInvalidArgumentException::class => UserError::class,
                ],
                true,
                [
                    'errors' => [
                        [
                            'message' => 'Error with invalid argument exception',
                            'extensions' => ['category' => 'user'],
                        ],
                    ],
                ],
            ],
            'with $mapExceptionsToParent and only the parent class' => [
                [
                    InvalidArgumentException::class => UserWarning::class,
                ],
                true,
                [
                    'extensions' => [
                        'warnings' => [
                            [
                                'message' => 'Error with invalid argument exception',
                                'extensions' => ['category' => 'user'],
                            ],
                        ],
                    ],
                ],
            ],
            'with $mapExceptionsToParent and the exact class first matches exact class' => [
                [
                    ChildOfInvalidArgumentException::class => UserError::class,
                    InvalidArgumentException::class => UserWarning::class,
                ],
                true,
                [
                    'errors' => [
                        [
                            'message' => 'Error with invalid argument exception',
                            'extensions' => ['category' => 'user'],
                        ],
                    ],
                ],
            ],
            'with $mapExceptionsToParent and the exact class first but parent maps to error' => [
                [
                    ChildOfInvalidArgumentException::class => UserWarning::class,
                    InvalidArgumentException::class => UserError::class,
                ],
                true,
                [
                    'extensions' => [
                        'warnings' => [
                            [
                                'message' => 'Error with invalid argument exception',
                                'extensions' => ['category' => 'user'],
                            ],
                        ],
                    ],
                ],
            ],
            'with $mapExceptionsToParent and the parent class first still matches exact class' => [
                [
                    InvalidArgumentException::class => UserWarning::class,
                    ChildOfInvalidArgumentException::class => UserError::class,
                ],
                true,
                [
                    'errors' => [
                        [
                            'message' => 'Error with invalid argument exception',
                            'extensions' => ['category' => 'user'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
