<?php

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;

final class ErrorHandlerListener
{
    /** @var ErrorHandler */
    private $errorHandler;

    /** @var bool */
    private $throwException;

    /** @var bool */
    private $debug;

    public function __construct(ErrorHandler $errorHandler, $throwException = false, $debug = false)
    {
        $this->errorHandler = $errorHandler;
        $this->throwException = $throwException;
        $this->debug = $debug;
    }

    public function onPostExecutor(ExecutorResultEvent $executorResultEvent)
    {
        $result = $executorResultEvent->getResult();
        $this->errorHandler->handleErrors($result, $this->throwException, $this->debug);
    }
}
