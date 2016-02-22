<?php

namespace Overblog\GraphQLBundle\Request;

use GraphQL\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Schema;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\ExecutorContextEvent;

class Executor
{
    private $schema;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /** @var boolean */
    private $throwException;

    /** @var ErrorHandler */
    private $errorHandler;

    public function __construct(Schema $schema, EventDispatcherInterface $dispatcher, $throwException, ErrorHandler $errorHandler)
    {
        $this->schema = $schema;
        $this->dispatcher = $dispatcher;
        $this->throwException = (bool)$throwException;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @return boolean
     */
    public function getThrowException()
    {
        return $this->throwException;
    }

    /**
     * @param boolean $throwException
     * @return $this
     */
    public function setThrowException($throwException)
    {
        $this->throwException = (bool)$throwException;

        return $this;
    }

    public function execute(array $data, array $context = [])
    {
        $event = new ExecutorContextEvent($context);
        $this->dispatcher->dispatch(Events::EXECUTOR_CONTEXT, $event);

        try {
            $executionResult = GraphQL::executeAndReturnResult(
                $this->schema,
                isset($data['query']) ? $data['query'] : null,
                $event->getExecutorContext(),
                $data['variables'],
                $data['operationName']
            );
        } catch (\Exception $exception) {
            $executionResult = new ExecutionResult(
                null,
                [new Error('An errors occurred while processing query.', null, $exception)]
            );
        }

        $this->errorHandler->handleErrors($executionResult, $this->throwException);

        return $executionResult;
    }
}
