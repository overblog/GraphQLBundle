<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\EventListener;

use Exception;
use Generator;
use GraphQL\Error\Error;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;
use Overblog\GraphQLBundle\EventListener\ErrorLoggerListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function sprintf;

final class ErrorLoggerListenerTest extends TestCase
{
    private ErrorLoggerListener $listener;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new ErrorLoggerListener($this->logger);
    }

    /**
     * @param mixed $expectedLoggerCalls
     */
    #[DataProvider('onErrorFormattingDataProvider')]
    public function testOnErrorFormatting(Error $error, $expectedLoggerCalls, array $expectedLoggerMethodArguments): void
    {
        if (is_callable($expectedLoggerCalls)) {
            $expectedLoggerCalls = $expectedLoggerCalls($this);
        }

        foreach ($expectedLoggerMethodArguments as $key => $value) {
            if (is_callable($value)) {
                $expectedLoggerMethodArguments[$key] = $value($this);
            }
        }

        $this->logger->expects($expectedLoggerCalls)
            ->method('log')
            ->with(...$expectedLoggerMethodArguments);

        $this->listener->onErrorFormatting(new ErrorFormattingEvent($error, []));
    }

    public static function onErrorFormattingDataProvider(): Generator
    {
        $exception = new Exception('Ko!');

        yield [
            new Error('Basic error'),
            fn (TestCase $test) => $test->never(),
            [fn (TestCase $test) => $test->anything()],
        ];

        yield [
            new Error('Wrapped Base UserError without previous', null, null, [], null, new UserError('User error message')),
            fn (TestCase $test) => $test->never(),
            [fn (TestCase $test) => $test->anything()],
        ];

        yield [
            new Error('Wrapped UserError without previous', null, null, [], null, new UserError('User error message')),
            fn (TestCase $test) => $test->never(),
            [fn (TestCase $test) => $test->anything()],
        ];

        yield [
            new Error('Wrapped UserWarning without previous', null, null, [], null, new UserWarning('User warning message')),
            fn (TestCase $test) => $test->never(),
            [fn (TestCase $test) => $test->anything()],
        ];

        yield [
            new Error('Wrapped unknown exception', null, null, [], null, $exception),
            fn (TestCase $test) => $test->once(),
            [
                LogLevel::CRITICAL,
                sprintf('[GraphQL] Exception: Ko![0] (caught throwable) at %s line %s.', __FILE__, $exception->getLine()),
                ['exception' => $exception],
            ],
        ];

        yield [
            new Error('Wrapped Base UserError with previous', null, null, [], null, new UserError('User error message', 0, $exception)),
            fn (TestCase $test) => $test->once(),
            [
                LogLevel::ERROR,
                sprintf('[GraphQL] Exception: Ko![0] (caught throwable) at %s line %s.', __FILE__, $exception->getLine()),
                ['exception' => $exception],
            ],
        ];

        yield [
            new Error('Wrapped UserError with previous', null, null, [], null, new UserError('User error message', 0, $exception)),
            fn (TestCase $test) => $test->once(),
            [
                LogLevel::ERROR,
                sprintf('[GraphQL] Exception: Ko![0] (caught throwable) at %s line %s.', __FILE__, $exception->getLine()),
                ['exception' => $exception],
            ],
        ];

        yield [
            new Error('Wrapped UserWarning with previous', null, null, [], null, new UserWarning('User warning message', 0, $exception)),
            fn (TestCase $test) => $test->once(),
            [
                LogLevel::WARNING,
                sprintf('[GraphQL] Exception: Ko![0] (caught throwable) at %s line %s.', __FILE__, $exception->getLine()),
                ['exception' => $exception],
            ],
        ];
    }
}
