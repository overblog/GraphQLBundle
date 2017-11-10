<?php

namespace Overblog\GraphQLBundle\Tests\Error;

use GraphQL\Error\Error as GraphQLError;
use GraphQL\Error\UserError as GraphQLUserError;
use GraphQL\Executor\ExecutionResult;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Error\UserErrors;
use Overblog\GraphQLBundle\Error\UserWarning;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    /** @var ErrorHandler */
    private $errorHandler;

    public function setUp()
    {
        $this->errorHandler = new ErrorHandler();
    }

    public function testMaskErrorWithThrowExceptionSetToFalse()
    {
        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError('Error without wrapped exception'),
                new GraphQLError('Error with wrapped exception', null, null, null, null, new \Exception('My Exception message')),
                new GraphQLError('Error with wrapped user error', null, null, null, null, new UserError('My User Error')),
                new GraphQLError('', null, null, null, null, new UserErrors(['My User Error 1', 'My User Error 2', new UserError('My User Error 3')])),
                new GraphQLError('Error with wrapped user warning', null, null, null, null, new UserWarning('My User Warning')),
                new GraphQLError('Invalid value!', null, null, null, null, new GraphQLUserError('Invalid value!')),
            ]
        );

        $this->errorHandler->handleErrors($executionResult);

        $expected = [
            'errors' => [
                [
                    'message' => 'Error without wrapped exception',
                ],
                [
                    'message' => ErrorHandler::DEFAULT_ERROR_MESSAGE,
                ],
                [
                    'message' => 'Error with wrapped user error',
                ],
                [
                    'message' => 'My User Error 1',
                ],
                [
                    'message' => 'My User Error 2',
                ],
                [
                    'message' => 'My User Error 3',
                ],
                [
                    'message' => 'Invalid value!',
                ],
            ],
            'extensions' => [
                'warnings' => [
                    [
                        'message' => 'Error with wrapped user warning',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $executionResult->toArray());
    }

    public function testMaskErrorWithWrappedExceptionAndThrowExceptionSetToTrue()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('My Exception message');

        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError('Error with wrapped exception', null, null, null, null, new \Exception('My Exception message')),
            ]
        );

        $this->errorHandler->handleErrors($executionResult, true);
    }

    public function testInvalidUserErrorsItem()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Error must be string or instance of Overblog\GraphQLBundle\Error\UserError.');

        new UserErrors([
            'Some Error',
            false,
        ]);
    }

    public function testMaskErrorWithWrappedUserErrorAndThrowExceptionSetToTrue()
    {
        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError('Error with wrapped user error', null, null, null, null, new UserError('My User Error')),
            ]
        );

        $this->errorHandler->handleErrors($executionResult, true);

        $expected = [
            'errors' => [
                [
                    'message' => 'Error with wrapped user error',
                ],
            ],
        ];

        $this->assertEquals($expected, $executionResult->toArray());
    }

    public function testMaskErrorWithoutWrappedExceptionAndThrowExceptionSetToTrue()
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
                ],
            ],
        ];

        $this->assertEquals($expected, $executionResult->toArray());
    }

    public function testConvertExceptionToUserWarning()
    {
        $errorHandler = new ErrorHandler(null, null, [\InvalidArgumentException::class => UserWarning::class]);

        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError('Error with invalid argument exception', null, null, null, null, new \InvalidArgumentException('Invalid argument exception')),
            ]
        );

        $errorHandler->handleErrors($executionResult, true);

        $expected = [
            'extensions' => [
                'warnings' => [
                    [
                        'message' => 'Error with invalid argument exception',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $executionResult->toArray());
    }

    /**
     * @param array        $exceptionMap
     * @param bool         $mapExceptionsToParent
     * @param array|string $expectedUserError
     *
     * @dataProvider parentExceptionMappingDataProvider
     */
    public function testConvertExceptionUsingParentExceptionMatchesAlwaysFirstExactExceptionOtherwiseMatchesParent(array $exceptionMap, $mapExceptionsToParent, $expectedUserError)
    {
        $errorHandler = new ErrorHandler(null, null, $exceptionMap, $mapExceptionsToParent);

        $executionResult = new ExecutionResult(
            null,
            [
                new GraphQLError(
                    'Error with invalid argument exception',
                    null,
                    null,
                    null,
                    null,
                    new ChildOfInvalidArgumentException('Invalid argument exception')
                ),
            ]
        );

        if (is_string($expectedUserError)) {
            self::expectException($expectedUserError);
        }
        $errorHandler->handleErrors($executionResult, true);

        if (is_array($expectedUserError)) {
            $this->assertEquals($expectedUserError, $executionResult->toArray());
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
                        ],
                    ],
                ],
            ],
            'without $mapExceptionsToParent and only the parent class, does not map to parent' => [
                [
                    \InvalidArgumentException::class => UserWarning::class,
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
                        ],
                    ],
                ],
            ],
            'with $mapExceptionsToParent and only the parent class' => [
                [
                    \InvalidArgumentException::class => UserWarning::class,
                ],
                true,
                [
                    'extensions' => [
                        'warnings' => [
                            [
                                'message' => 'Error with invalid argument exception',
                            ],
                        ],
                    ],
                ],
            ],
            'with $mapExceptionsToParent and the exact class first matches exact class' => [
                [
                    ChildOfInvalidArgumentException::class => UserError::class,
                    \InvalidArgumentException::class => UserWarning::class,
                ],
                true,
                [
                    'errors' => [
                        [
                            'message' => 'Error with invalid argument exception',
                        ],
                    ],
                ],
            ],
            'with $mapExceptionsToParent and the exact class first but parent maps to error' => [
                [
                    ChildOfInvalidArgumentException::class => UserWarning::class,
                    \InvalidArgumentException::class => UserError::class,
                ],
                true,
                [
                    'extensions' => [
                        'warnings' => [
                            [
                                'message' => 'Error with invalid argument exception',
                            ],
                        ],
                    ],
                ],
            ],
            'with $mapExceptionsToParent and the parent class first still matches exact class' => [
                [
                    \InvalidArgumentException::class => UserWarning::class,
                    ChildOfInvalidArgumentException::class => UserError::class,
                ],
                true,
                [
                    'errors' => [
                        [
                            'message' => 'Error with invalid argument exception',
                        ],
                    ],
                ],
            ],
        ];
    }
}
