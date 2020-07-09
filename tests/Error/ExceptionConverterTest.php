<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Error;

use Exception;
use Generator;
use Overblog\GraphQLBundle\Error\ExceptionConverter;
use Overblog\GraphQLBundle\Error\UserError;
use PHPUnit\Framework\TestCase;
use Throwable;
use function get_class;

final class ExceptionConverterTest extends TestCase
{
    /**
     * @param array<string, string[]> $exceptionMap
     *
     * @dataProvider convertExceptionDataProvider
     */
    public function testConvertException(array $exceptionMap, bool $mapExceptionsToParent, Throwable $exception, Throwable $expectedException): void
    {
        $exceptionConverter = new ExceptionConverter($exceptionMap, $mapExceptionsToParent);
        $convertedException = $exceptionConverter->convertException($exception);

        $this->assertSame(
            $expectedException->getMessage(),
            $convertedException->getMessage()
        );

        $this->assertSame(
            $expectedException->getPrevious(),
            $convertedException->getPrevious()
        );
    }

    public function convertExceptionDataProvider(): Generator
    {
        yield [
            [],
            false,
            new Exception('foo'),
            new Exception('foo'),
        ];

        yield [
            [],
            true,
            new Exception('foo'),
            new Exception('foo'),
        ];

        yield [
            [],
            false,
            new UserError('foo'),
            new UserError('foo'),
        ];

        $exception = new class() extends Exception {
            public function __construct()
            {
                parent::__construct('foo');
            }
        };

        yield [
            [
                Exception::class => UserError::class,
            ],
            false,
            $exception,
            $exception,
        ];

        $exception = new class() extends Exception {
            public function __construct()
            {
                parent::__construct('foo');
            }
        };

        yield [
            [
                get_class($exception) => UserError::class,
            ],
            false,
            $exception,
            new UserError('foo', 0, $exception),
        ];

        $exception = new class() extends Exception {
            public function __construct()
            {
                parent::__construct('foo');
            }
        };

        yield [
            [
                Exception::class => UserError::class,
            ],
            true,
            $exception,
            new UserError('foo', 0, $exception),
        ];
    }
}
