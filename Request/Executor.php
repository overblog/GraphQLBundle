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

use GraphQL\GraphQL;
use GraphQL\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\ExecutorContextEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Executor
{
    private $schema;

    /**
     * @var EventDispatcherInterface|null
     */
    private $dispatcher;

    /** @var bool */
    private $throwException;

    /** @var ErrorHandler|null */
    private $errorHandler;

    public function __construct(Schema $schema, EventDispatcherInterface $dispatcher = null, $throwException = false, ErrorHandler $errorHandler = null)
    {
        $this->schema = $schema;
        $this->dispatcher = $dispatcher;
        $this->throwException = (bool) $throwException;
        $this->errorHandler = $errorHandler;
    }

    public function setMaxQueryDepth($maxQueryDepth)
    {
        /** @var QueryDepth $queryDepth */
        $queryDepth = DocumentValidator::getRule('QueryDepth');
        $queryDepth->setMaxQueryDepth($maxQueryDepth);
    }

    public function setMaxQueryComplexity($maxQueryComplexity)
    {
        /** @var QueryComplexity $queryComplexity */
        $queryComplexity = DocumentValidator::getRule('QueryComplexity');
        $queryComplexity->setMaxQueryComplexity($maxQueryComplexity);
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
        if (null !== $this->dispatcher) {
            $event = new ExecutorContextEvent($context);
            $this->dispatcher->dispatch(Events::EXECUTOR_CONTEXT, $event);
            $context = $event->getExecutorContext();
        }

        $executionResult = GraphQL::executeAndReturnResult(
            $this->schema,
            isset($data['query']) ? $data['query'] : null,
            $context,
            $context,
            $data['variables'],
            $data['operationName']
        );

        if (null !== $this->errorHandler) {
            $this->errorHandler->handleErrors($executionResult, $this->throwException);
        }

        return $executionResult;
    }
}
