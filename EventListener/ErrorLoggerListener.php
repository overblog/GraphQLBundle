<?php

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class ErrorLoggerListener
{
    const DEFAULT_LOGGER_SERVICE = 'logger';

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $userErrorClass;

    /** @var string */
    private $userWarningClass;

    public function __construct(
        LoggerInterface $logger = null,
        $userErrorClass = ErrorHandler::DEFAULT_USER_ERROR_CLASS,
        $userWarningClass = ErrorHandler::DEFAULT_USER_WARNING_CLASS
    ) {
        $this->logger = null === $logger ? new NullLogger() : $logger;
        $this->userErrorClass = $userErrorClass;
        $this->userWarningClass = $userWarningClass;
    }

    public function onErrorFormatting(ErrorFormattingEvent $event)
    {
        $error = $event->getError();
        if ($error->getPrevious()) {
            $exception = $error->getPrevious();
            if ($exception->getPrevious()) {
                if ($exception instanceof $this->userErrorClass) {
                    $this->logException($exception->getPrevious());

                    return;
                }

                if ($exception instanceof $this->userWarningClass) {
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
