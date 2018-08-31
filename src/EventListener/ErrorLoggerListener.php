<?php

namespace Overblog\GraphQLBundle\EventListener;

use GraphQL\Error\UserError;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class ErrorLoggerListener
{
    const DEFAULT_LOGGER_SERVICE = 'logger';

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        LoggerInterface $logger = null
    ) {
        $this->logger = null === $logger ? new NullLogger() : $logger;
    }

    public function onErrorFormatting(ErrorFormattingEvent $event)
    {
        $error = $event->getError();

        if ($error->getPrevious()) {
            $exception = $error->getPrevious();
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
            $this->log($error->getPrevious(), LogLevel::CRITICAL);
        }
    }

    /**
     * @param \Throwable $throwable
     * @param string     $errorLevel
     */
    public function log($throwable, $errorLevel = LogLevel::ERROR)
    {
        $this->logger->$errorLevel(self::serializeThrowableObject($throwable), ['throwable' => $throwable]);
    }

    /**
     * @param \Throwable $throwable
     *
     * @return string
     */
    private static function serializeThrowableObject($throwable)
    {
        $message = \sprintf(
            '[GraphQL] %s: %s[%d] (caught throwable) at %s line %s.',
            \get_class($throwable),
            $throwable->getMessage(),
            $throwable->getCode(),
            $throwable->getFile(),
            $throwable->getLine()
        );

        return $message;
    }
}
