<?php

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Error\UserError;
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
            if ($exception->getPrevious()) {
                if ($exception instanceof UserError) {
                    $this->logException($exception->getPrevious());

                    return;
                }

                if ($exception instanceof UserWarning) {
                    $this->logException($exception->getPrevious(), LogLevel::WARNING);

                    return;
                }
            }
            $this->logException($error->getPrevious(), LogLevel::CRITICAL);
        }
    }

    /**
     * @param \Throwable $exception
     * @param string     $errorLevel
     */
    public function logException($exception, $errorLevel = LogLevel::ERROR)
    {
        $message = sprintf(
            '%s: %s[%d] (caught exception) at %s line %s.',
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine()
        );

        $this->logger->$errorLevel($message, ['exception' => $exception]);
    }
}
