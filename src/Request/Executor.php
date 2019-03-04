<?php

namespace Overblog\GraphQLBundle\Request;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\ExecutorArgumentsEvent;
use Overblog\GraphQLBundle\Event\ExecutorContextEvent;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;
use Overblog\GraphQLBundle\Executor\ExecutorInterface;
use Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Executor
{
    const PROMISE_ADAPTER_SERVICE_ID = 'overblog_graphql.promise_adapter';

    /** @var Schema[] */
    private $schemas = [];

    /** @var EventDispatcherInterface|null */
    private $dispatcher;

    /** @var ExecutorInterface */
    private $executor;

    /** @var PromiseAdapter */
    private $promiseAdapter;

    /** @var callable|null */
    private $defaultFieldResolver;

    public function __construct(
        ExecutorInterface $executor,
        EventDispatcherInterface $dispatcher,
        PromiseAdapter $promiseAdapter,
        callable $defaultFieldResolver = null
    ) {
        $this->executor = $executor;
        $this->dispatcher = $dispatcher;
        $this->promiseAdapter = $promiseAdapter;
        $this->defaultFieldResolver = $defaultFieldResolver;
    }

    public function setExecutor(ExecutorInterface $executor)
    {
        $this->executor = $executor;

        return $this;
    }

    public function setPromiseAdapter(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;

        return $this;
    }

    /**
     * @param string $name
     * @param Schema $schema
     *
     * @return $this
     */
    public function addSchema($name, Schema $schema)
    {
        $this->schemas[$name] = $schema;

        return $this;
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
            $schema = \array_values($this->schemas)[0];
        } else {
            if (!isset($this->schemas[$name])) {
                throw new NotFoundHttpException(\sprintf('Could not found "%s" schema.', $name));
            }
            $schema = $this->schemas[$name];
        }

        return $schema;
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

    public function enableIntrospectionQuery()
    {
        DocumentValidator::addRule(new DisableIntrospection(DisableIntrospection::DISABLED));
    }

    public function disableIntrospectionQuery()
    {
        DocumentValidator::addRule(new DisableIntrospection());
    }

    /**
     * @param string|null                    $schemaName
     * @param array                          $request
     * @param array|\ArrayObject|object|null $rootValue
     *
     * @return ExecutionResult
     */
    public function execute($schemaName, array $request, $rootValue = null)
    {
        $executorArgumentsEvent = $this->preExecute(
            $this->getSchema($schemaName),
            isset($request[ParserInterface::PARAM_QUERY]) ? $request[ParserInterface::PARAM_QUERY] : null,
            new \ArrayObject(),
            $rootValue,
            $request[ParserInterface::PARAM_VARIABLES],
            isset($request[ParserInterface::PARAM_OPERATION_NAME]) ? $request[ParserInterface::PARAM_OPERATION_NAME] : null
        );

        $executorArgumentsEvent->getSchema()->processExtensions();

        $result = $this->executor->execute(
            $executorArgumentsEvent->getSchema(),
            $executorArgumentsEvent->getRequestString(),
            $executorArgumentsEvent->getRootValue(),
            $executorArgumentsEvent->getContextValue(),
            $executorArgumentsEvent->getVariableValue(),
            $executorArgumentsEvent->getOperationName()
        );

        $result = $this->postExecute($result);

        return $result;
    }

    /**
     * @param Schema       $schema
     * @param string       $requestString
     * @param \ArrayObject $contextValue
     * @param mixed        $rootValue
     * @param array|null   $variableValue
     * @param string|null  $operationName
     *
     * @return ExecutorArgumentsEvent
     */
    private function preExecute(
        Schema $schema,
        $requestString,
        \ArrayObject $contextValue,
        $rootValue = null,
        array $variableValue = null,
        $operationName = null
    ) {
        $this->checkPromiseAdapter();

        $this->executor->setPromiseAdapter($this->promiseAdapter);
        // this is needed when not using only generated types
        if ($this->defaultFieldResolver) {
            $this->executor->setDefaultFieldResolver($this->defaultFieldResolver);
        }
        $this->dispatcher->dispatch(Events::EXECUTOR_CONTEXT, new ExecutorContextEvent($contextValue));

        return $this->dispatcher->dispatch(
            Events::PRE_EXECUTOR,
            ExecutorArgumentsEvent::create($schema, $requestString, $contextValue, $rootValue, $variableValue, $operationName)
        );
    }

    /**
     * @param ExecutionResult|Promise $result
     *
     * @return ExecutionResult
     */
    private function postExecute($result)
    {
        if ($result instanceof Promise) {
            $result = $this->promiseAdapter->wait($result);
        }

        $this->checkExecutionResult($result);

        return  $this->dispatcher->dispatch(Events::POST_EXECUTOR, new ExecutorResultEvent($result))->getResult();
    }

    private function checkPromiseAdapter()
    {
        if ($this->promiseAdapter && !$this->promiseAdapter instanceof PromiseAdapterInterface && !\is_callable([$this->promiseAdapter, 'wait'])) {
            throw new \RuntimeException(
                \sprintf(
                    'PromiseAdapter should be an object instantiating "%s" or "%s" with a "wait" method.',
                    PromiseAdapterInterface::class,
                    PromiseAdapter::class
                )
            );
        }
    }

    private function checkExecutionResult($result)
    {
        if (!\is_object($result) || !$result instanceof ExecutionResult) {
            throw new \RuntimeException(
                \sprintf('Execution result should be an object instantiating "%s".', ExecutionResult::class)
            );
        }
    }
}
