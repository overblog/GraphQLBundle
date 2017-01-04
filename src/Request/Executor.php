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

use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\ExecutorContextEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Executor
{
    const DEFAULT_EXECUTOR = 'GraphQL\\GraphQL::executeAndReturnResult';

    /**
     * @var Schema[]
     */
    private $schemas;

    /**
     * @var EventDispatcherInterface|null
     */
    private $dispatcher;

    /** @var bool */
    private $throwException;

    /** @var ErrorHandler|null */
    private $errorHandler;

    /** @var bool */
    private $hasDebugInfo;

    /**
     * @var callable
     */
    private $executor;

    public function __construct(
        EventDispatcherInterface $dispatcher = null,
        $throwException = false,
        ErrorHandler $errorHandler = null,
        $hasDebugInfo = false,
        callable $executor = null
    ) {
        $this->dispatcher = $dispatcher;
        $this->throwException = (bool) $throwException;
        $this->errorHandler = $errorHandler;
        $hasDebugInfo ? $this->enabledDebugInfo() : $this->disabledDebugInfo();
        $this->executor = $executor;
        if (null === $this->executor) {
            $this->executor = self::DEFAULT_EXECUTOR;
        }
    }

    public function setExecutor(callable $executor)
    {
        $this->executor = $executor;

        return $this;
    }

    public function addSchema($name, Schema $schema)
    {
        $this->schemas[$name] = $schema;

        return $this;
    }

    public function enabledDebugInfo()
    {
        $this->hasDebugInfo = true;

        return $this;
    }

    public function disabledDebugInfo()
    {
        $this->hasDebugInfo = false;

        return $this;
    }

    public function hasDebugInfo()
    {
        return $this->hasDebugInfo;
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

    public function execute(array $data, array $context = [], $schemaName = null)
    {
        if (null !== $this->dispatcher) {
            $event = new ExecutorContextEvent($context);
            $this->dispatcher->dispatch(Events::EXECUTOR_CONTEXT, $event);
            $context = $event->getExecutorContext();
        }

        $schema = $this->getSchema($schemaName);

        $startTime = microtime(true);
        $startMemoryUsage = memory_get_usage(true);

        $executionResult = call_user_func(
            $this->executor,
            $schema,
            isset($data[ParserInterface::PARAM_QUERY]) ? $data[ParserInterface::PARAM_QUERY] : null,
            $context,
            $context,
            $data[ParserInterface::PARAM_VARIABLES],
            isset($data[ParserInterface::PARAM_OPERATION_NAME]) ? $data[ParserInterface::PARAM_OPERATION_NAME] : null
        );

        if (!is_object($executionResult) || !$executionResult instanceof ExecutionResult) {
            throw new \RuntimeException(sprintf('Execution result should be an object instantiating "%s".', 'GraphQL\\Executor\\ExecutionResult'));
        }

        if ($this->hasDebugInfo()) {
            $executionResult->extensions['debug'] = [
                'executionTime' => sprintf('%d ms', round(microtime(true) - $startTime, 3) * 1000),
                'memoryUsage' => sprintf('%.2F MiB', (memory_get_usage(true) - $startMemoryUsage) / 1024 / 1024),
            ];
        }

        if (null !== $this->errorHandler) {
            $this->errorHandler->handleErrors($executionResult, $this->throwException);
        }

        return $executionResult;
    }

    /**
     * @param string|null $name
     *
     * @return Schema
     */
    public function getSchema($name = null)
    {
        if (empty($this->schemas)) {
            throw new \RuntimeException('At least one schema should be declare.');
        }

        if (null === $name) {
            $schema = array_values($this->schemas)[0];
        } else {
            if (!isset($this->schemas[$name])) {
                throw new NotFoundHttpException(sprintf('Could not found "%s" schema.', $name));
            }
            $schema = $this->schemas[$name];
        }

        return $schema;
    }
}
