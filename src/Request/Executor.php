<?php

declare(strict_types=1);

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
    public const PROMISE_ADAPTER_SERVICE_ID = 'overblog_graphql.promise_adapter';

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
        ?callable $defaultFieldResolver = null
    ) {
        $this->executor = $executor;
        $this->dispatcher = $dispatcher;
        $this->promiseAdapter = $promiseAdapter;
        $this->defaultFieldResolver = $defaultFieldResolver;
    }

    public function setExecutor(ExecutorInterface $executor): self
    {
        $this->executor = $executor;

        return $this;
    }

    public function setPromiseAdapter(PromiseAdapter $promiseAdapter): self
    {
        $this->promiseAdapter = $promiseAdapter;

        return $this;
    }

    /**
     * @param string $name
     * @param Schema $schema
     *
     * @return self
     */
    public function addSchema(string $name, Schema $schema): self
    {
        $this->schemas[$name] = $schema;

        return $this;
    }

    /**
     * @param string|null $name
     *
     * @return Schema
     */
    public function getSchema(?string $name = null): Schema
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

    public function setMaxQueryDepth($maxQueryDepth): void
    {
        /** @var QueryDepth $queryDepth */
        $queryDepth = DocumentValidator::getRule('QueryDepth');
        $queryDepth->setMaxQueryDepth($maxQueryDepth);
    }

    public function setMaxQueryComplexity($maxQueryComplexity): void
    {
        /** @var QueryComplexity $queryComplexity */
        $queryComplexity = DocumentValidator::getRule('QueryComplexity');
        $queryComplexity->setMaxQueryComplexity($maxQueryComplexity);
    }

    public function disableIntrospectionQuery(): void
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
    public function execute(?string $schemaName = null, array $request, $rootValue = null): ExecutionResult
    {
        $executorArgumentsEvent = $this->preExecute(
            $this->getSchema($schemaName),
            $request[ParserInterface::PARAM_QUERY] ?? null,
            new \ArrayObject(),
            $rootValue,
            $request[ParserInterface::PARAM_VARIABLES],
            $request[ParserInterface::PARAM_OPERATION_NAME] ?? null
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

    private function preExecute(
        Schema $schema,
        ?string $requestString,
        \ArrayObject $contextValue,
        $rootValue = null,
        ?array $variableValue = null,
        ?string $operationName = null
    ): ExecutorArgumentsEvent {
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
    private function postExecute($result): ExecutionResult
    {
        if ($result instanceof Promise) {
            $result = $this->promiseAdapter->wait($result);
        }

        $this->checkExecutionResult($result);

        return $this->dispatcher->dispatch(Events::POST_EXECUTOR, new ExecutorResultEvent($result))->getResult();
    }

    private function checkPromiseAdapter(): void
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

    private function checkExecutionResult($result): void
    {
        if (!\is_object($result) || !$result instanceof ExecutionResult) {
            throw new \RuntimeException(
                \sprintf('Execution result should be an object instantiating "%s".', ExecutionResult::class)
            );
        }
    }
}
