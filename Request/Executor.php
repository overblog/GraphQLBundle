<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Request;

use GraphQL\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Schema;
use Overblog\GraphQLBundle\Error\ErrorHandler;
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

    /** @var bool */
    private $throwException;

    /** @var ErrorHandler */
    private $errorHandler;

    public function __construct(Schema $schema, EventDispatcherInterface $dispatcher, $throwException, ErrorHandler $errorHandler)
    {
        $this->schema = $schema;
        $this->dispatcher = $dispatcher;
        $this->throwException = (bool) $throwException;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @return bool
     */
    public function getThrowException()
    {
        return $this->throwException;
    }

    /**
     * @param bool $throwException
     *
     * @return $this
     */
    public function setThrowException($throwException)
    {
        $this->throwException = (bool) $throwException;

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
