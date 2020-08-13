<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\EventListener;

use GraphQL\Error\UserError;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Throwable;
use function get_class;
use function sprintf;

final class ErrorLoggerListener
{
    public const DEFAULT_LOGGER_SERVICE = 'logger';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function onErrorFormatting(ErrorFormattingEvent $event): void
    {
        $error = $event->getError();
        $exception = $error->getPrevious();

        if (null === $exception) {
            return;
        }

        if ($exception instanceof UserError) {
            if ($exception->getPrevious()) {
                $this->log($exception->getPrevious());
            }

            return;
        }

        if ($exception instanceof UserWarning) {
            if ($exception->getPrevious()) {
                $this->log($exception->getPrevious(), LogLevel::WARNING);
            }

            return;
        }

        $this->log($exception, LogLevel::CRITICAL);
    }

    public function log(Throwable $exception, string $errorLevel = LogLevel::ERROR): void
    {
        $message = sprintf(
            '[GraphQL] %s: %s[%d] (caught throwable) at %s line %s.',
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine()
        );

        $this->logger->log($errorLevel, $message, ['exception' => $exception]);
    }
}
