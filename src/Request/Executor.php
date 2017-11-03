<?php

namespace Overblog\GraphQLBundle\Request;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\ExecutorContextEvent;
use Overblog\GraphQLBundle\Executor\ExecutorInterface;
use Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Executor
{
    const PROMISE_ADAPTER_SERVICE_ID = 'overblog_graphql.promise_adapter';

    /** @var Schema[] */
    private $schemas;

    /** @var EventDispatcherInterface|null */
    private $dispatcher;

    /** @var bool */
    private $throwException;

    /** @var ErrorHandler|null */
    private $errorHandler;

    /** @var bool */
    private $hasDebugInfo;

    /** @var ExecutorInterface */
    private $executor;

    /** @var PromiseAdapter */
    private $promiseAdapter;

    /** @var callable|null */
    private $defaultFieldResolver;

    public function __construct(
        ExecutorInterface $executor,
        EventDispatcherInterface $dispatcher = null,
        $throwException = false,
        ErrorHandler $errorHandler = null,
        $hasDebugInfo = false,
        PromiseAdapter $promiseAdapter = null,
        callable $defaultFieldResolver = null
    ) {
        $this->executor = $executor;
        $this->dispatcher = $dispatcher;
        $this->throwException = (bool) $throwException;
        $this->errorHandler = $errorHandler;
        $hasDebugInfo ? $this->enabledDebugInfo() : $this->disabledDebugInfo();
        $this->promiseAdapter = $promiseAdapter;
        $this->defaultFieldResolver = $defaultFieldResolver;
    }

    public function setExecutor(ExecutorInterface $executor)
    {
        $this->executor = $executor;

        return $this;
    }

    public function setPromiseAdapter(PromiseAdapter $promiseAdapter = null)
    {
        $this->promiseAdapter = $promiseAdapter;

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

        if ($this->promiseAdapter) {
            if (!$this->promiseAdapter instanceof PromiseAdapterInterface && !is_callable([$this->promiseAdapter, 'wait'])) {
                throw new \RuntimeException(
                    sprintf(
                        'PromiseAdapter should be an object instantiating "%s" or "%s" with a "wait" method.',
                        PromiseAdapterInterface::class,
                        PromiseAdapter::class
                    )
                );
            }
        }

        $schema = $this->getSchema($schemaName);

        $startTime = microtime(true);
        $startMemoryUsage = memory_get_usage(true);

        $this->executor->setPromiseAdapter($this->promiseAdapter);
        // this is needed when not using only generated types
        if ($this->defaultFieldResolver) {
            $this->executor->setDefaultFieldResolver($this->defaultFieldResolver);
        }

        $result = $this->executor->execute(
            $schema,
            isset($data[ParserInterface::PARAM_QUERY]) ? $data[ParserInterface::PARAM_QUERY] : null,
            $context,
            $context,
            $data[ParserInterface::PARAM_VARIABLES],
            isset($data[ParserInterface::PARAM_OPERATION_NAME]) ? $data[ParserInterface::PARAM_OPERATION_NAME] : null
        );

        if ($this->promiseAdapter) {
            $result = $this->promiseAdapter->wait($result);
        }

        if (!is_object($result) || !$result instanceof ExecutionResult) {
            throw new \RuntimeException(
                sprintf('Execution result should be an object instantiating "%s".', ExecutionResult::class)
            );
        }

        return $this->prepareResult($result, $startTime, $startMemoryUsage);
    }

    /**
     * @param ExecutionResult $result
     * @param int             $startTime
     * @param int             $startMemoryUsage
     *
     * @return ExecutionResult
     */
    private function prepareResult($result, $startTime, $startMemoryUsage)
    {
        if ($this->hasDebugInfo()) {
            $result->extensions['debug'] = [
                'executionTime' => sprintf('%d ms', round(microtime(true) - $startTime, 3) * 1000),
                'memoryUsage' => sprintf('%.2F MiB', (memory_get_usage(true) - $startMemoryUsage) / 1024 / 1024),
            ];
        }

        if (null !== $this->errorHandler) {
            $this->errorHandler->handleErrors($result, $this->throwException);
        }

        return $result;
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
