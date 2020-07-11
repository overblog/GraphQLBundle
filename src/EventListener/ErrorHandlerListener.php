<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;

final class ErrorHandlerListener
{
    private ErrorHandler $errorHandler;
    private bool $throwException;
    private bool $debug;

    public function __construct(ErrorHandler $errorHandler, bool $throwException = false, bool $debug = false)
    {
        $this->errorHandler = $errorHandler;
        $this->throwException = $throwException;
        $this->debug = $debug;
    }

    public function onPostExecutor(ExecutorResultEvent $executorResultEvent): void
    {
        $result = $executorResultEvent->getResult();

        $this->errorHandler->handleErrors($result, $this->throwException, $this->debug);
    }
}
