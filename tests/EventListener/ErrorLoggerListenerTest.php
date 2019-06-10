<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\EventListener;

use GraphQL\Error\Error;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;
use Overblog\GraphQLBundle\EventListener\ErrorLoggerListener;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ErrorLoggerListenerTest extends TestCase
{
    /** @var ErrorLoggerListener */
    private $listener;

    /** @var LoggerInterface|MockObject */
    private $logger;

    public function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->listener = new ErrorLoggerListener($this->logger);
    }

    /**
     * @param Error             $error
     * @param Invocation        $loggerExpects
     * @param Constraint|string $loggerMethod
     * @param array|null        $with
     *
     * @dataProvider fixtures
     */
    public function testOnErrorFormatting(Error $error, $loggerExpects, $loggerMethod, array $with = null): void
    {
        $mock = $this->logger->expects($loggerExpects)->method($loggerMethod);
        if ($with) {
            $mock->with(...$with);
        }

        $this->listener->onErrorFormatting(new ErrorFormattingEvent($error, []));
    }

    public function fixtures()
    {
        try {
            throw new \Exception('Ko!');
        } catch (\Exception $exception) {
        }
        $with = [
            \sprintf('[GraphQL] %s: %s[%d] (caught throwable) at %s line %s.', \Exception::class, 'Ko!', 0, __FILE__, $exception->getLine()),
            ['throwable' => $exception],
        ];

        return [
            [self::createError('Basic error'), $this->never(), $this->anything()],
            [self::createError('Wrapped Base UserError without previous', new \GraphQL\Error\UserError('User error message')), $this->never(), $this->anything()],
            [self::createError('Wrapped UserError without previous', new UserError('User error message')), $this->never(), $this->anything()],
            [self::createError('Wrapped UserWarning without previous', new UserWarning('User warning message')), $this->never(), $this->anything()],
            [self::createError('Wrapped unknown exception', $exception), $this->once(), 'critical', $with],
            [self::createError('Wrapped Base UserError with previous', new \GraphQL\Error\UserError('User error message', 0, $exception)), $this->once(), 'error', $with],
            [self::createError('Wrapped UserError with previous', new UserError('User error message', 0, $exception)), $this->once(), 'error', $with],
            [self::createError('Wrapped UserWarning with previous', new UserWarning('User warning message', 0, $exception)), $this->once(), 'warning', $with],
        ];
    }

    private static function createError($message, \Exception $exception = null)
    {
        return new Error($message, null, null, null, null, $exception);
    }
}
