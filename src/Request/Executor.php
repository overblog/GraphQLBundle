<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Request;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Overblog\GraphQLBundle\Event\EventDispatcherVersionHelper;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\ExecutorArgumentsEvent;
use Overblog\GraphQLBundle\Event\ExecutorContextEvent;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;
use Overblog\GraphQLBundle\Executor\ExecutorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Executor
{
    public const PROMISE_ADAPTER_SERVICE_ID = 'overblog_graphql.promise_adapter';

    private $schemas = [];

    private $dispatcher;

    private $promiseAdapter;

    private $executor;

    private $defaultFieldResolver;

    private $useExperimentalExecutor;

    public function __construct(
        ExecutorInterface $executor,
        PromiseAdapter $promiseAdapter,
        EventDispatcherInterface $dispatcher,
        ?callable $defaultFieldResolver = null,
        bool $useExperimental = false
    ) {
        $this->executor = $executor;
        $this->promiseAdapter = $promiseAdapter;
        $this->dispatcher = $dispatcher;
        $this->defaultFieldResolver = $defaultFieldResolver;
        $this->useExperimentalExecutor = $useExperimental;
    }

    public function setExecutor(ExecutorInterface $executor): self
    {
        $this->executor = $executor;

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

    public function enableIntrospectionQuery(): void
    {
        DocumentValidator::addRule(new DisableIntrospection(DisableIntrospection::DISABLED));
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
    public function execute(?string $schemaName, array $request, $rootValue = null): ExecutionResult
    {
        $this->useExperimentalExecutor ? GraphQL::useExperimentalExecutor() : GraphQL::useReferenceExecutor();

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
            $this->promiseAdapter,
            $executorArgumentsEvent->getSchema(),
            $executorArgumentsEvent->getRequestString(),
            $executorArgumentsEvent->getRootValue(),
            $executorArgumentsEvent->getContextValue(),
            $executorArgumentsEvent->getVariableValue(),
            $executorArgumentsEvent->getOperationName(),
            $this->defaultFieldResolver
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
        EventDispatcherVersionHelper::dispatch(
            $this->dispatcher,
            new ExecutorContextEvent($contextValue),
            Events::EXECUTOR_CONTEXT
        );

        return EventDispatcherVersionHelper::dispatch(
            $this->dispatcher,
            ExecutorArgumentsEvent::create($schema, $requestString, $contextValue, $rootValue, $variableValue, $operationName),
            Events::PRE_EXECUTOR
        );
    }

    private function postExecute(ExecutionResult $result): ExecutionResult
    {
        return EventDispatcherVersionHelper::dispatch(
            $this->dispatcher,
            new ExecutorResultEvent($result),
            Events::POST_EXECUTOR
        )->getResult();
    }
}
