<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ErrorHandlerListener
{
    public const DEFAULT_LOGGER_SERVICE = 'logger';

    /** @var ErrorHandler */
    private $errorHandler;

    /** @var bool */
    private $throwException;

    /** @var bool */
    private $debug;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ErrorHandler $errorHandler,
        LoggerInterface $logger,
        bool $throwException = false,
        bool $debug = false
    ) {
        $this->errorHandler = $errorHandler;
        $this->throwException = $throwException;
        $this->debug = $debug;
        $this->logger = null === $logger ? new NullLogger() : $logger;
    }

    public function onPostExecutor(ExecutorResultEvent $executorResultEvent): void
    {
        $result = $executorResultEvent->getResult();
        $this->errorHandler->handleErrors($result, $this->throwException, $this->debug);
        $result = $result->toArray();

        if (isset($result['errors'])) {
            $this->logger->error(__METHOD__.' : '.\json_encode($result['errors']));
        }
    }
}
