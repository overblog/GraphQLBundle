<?php

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;

class ErrorHandlerListener
{
    /** @var ErrorHandler */
    private $errorHandler;

    /** @var bool */
    private $throwException;

    public function __construct(ErrorHandler $errorHandler, $throwException = false)
    {
        $this->errorHandler = $errorHandler;
        $this->throwException = $throwException;
    }

    public function setThrowException($throwException)
    {
        $this->throwException = $throwException;
    }

    public function onPostExecutor(ExecutorResultEvent $executorResultEvent)
    {
        $result = $executorResultEvent->getResult();
        $this->errorHandler->handleErrors($result, $this->throwException);
    }
}
