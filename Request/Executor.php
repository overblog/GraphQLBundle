<?php

namespace Overblog\GraphQLBundle\Request;

use GraphQL\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Schema;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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

    public function __construct(Schema $schema, EventDispatcherInterface $dispatcher, $throwException, LoggerInterface $logger = null)
    {
        $this->schema = $schema;
        $this->dispatcher = $dispatcher;
        $this->throwException = (bool)$throwException;
        $this->logger = null === $logger ? new NullLogger(): $logger;
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
            if ($this->throwException && !empty($executionResult->errors)) {
                foreach ($executionResult->errors as $error) {
                    // if is a try catch exception wrapped in Error
                    if ($error->getPrevious() instanceof \Exception) {
                        throw $executionResult->errors[0]->getPrevious();
                    }
                }
            }

        } catch (\Exception $exception) {
            if ($this->throwException) {
                throw $exception;
            }
            $this->logger->error($exception->getMessage());

            $executionResult = new ExecutionResult(
                null,
                [new Error('An errors occurred while processing query.')]
            );
        }

        return $executionResult;
    }
}
