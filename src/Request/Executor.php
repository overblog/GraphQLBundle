<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Request;

use ArrayObject;
use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\ExecutorArgumentsEvent;
use Overblog\GraphQLBundle\Event\ExecutorContextEvent;
use Overblog\GraphQLBundle\Event\ExecutorResultEvent;
use Overblog\GraphQLBundle\Executor\ExecutorInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function array_keys;
use function is_callable;
use function sprintf;

class Executor
{
    public const PROMISE_ADAPTER_SERVICE_ID = 'overblog_graphql.promise_adapter';

    private array $schemas = [];
    private EventDispatcherInterface $dispatcher;
    private PromiseAdapter $promiseAdapter;
    private ExecutorInterface $executor;
    private bool $useExperimentalExecutor;

    /**
     * @var callable|null
     */
    private $defaultFieldResolver;

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

    public function addSchemaBuilder(string $name, Closure $builder): self
    {
        $this->schemas[$name] = $builder;

        return $this;
    }

    public function addSchema(string $name, Schema $schema): self
    {
        $this->schemas[$name] = $schema;

        return $this;
    }

    public function getSchema(string $name = null): Schema
    {
        if (empty($this->schemas)) {
            throw new RuntimeException('At least one schema should be declare.');
        }

        if (null === $name) {
            // TODO(mcg-web): Replace by array_key_first PHP 7 >= 7.3.0.
            foreach ($this->schemas as $name => $schema) {
                break;
            }
        }
        if (!isset($this->schemas[$name])) {
            throw new NotFoundHttpException(sprintf('Could not found "%s" schema.', $name));
        }
        $schema = $this->schemas[$name];
        if (is_callable($schema)) {
            $schema = $schema();
            $this->addSchema((string) $name, $schema);
        }

        return $schema;
    }

    public function getSchemasNames(): array
    {
        return array_keys($this->schemas);
    }

    public function setMaxQueryDepth(int $maxQueryDepth): void
    {
        /** @var QueryDepth $queryDepth */
        $queryDepth = DocumentValidator::getRule('QueryDepth');
        $queryDepth->setMaxQueryDepth($maxQueryDepth);
    }

    public function setMaxQueryComplexity(int $maxQueryComplexity): void
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
     * @param array|ArrayObject|object|null $rootValue
     */
    public function execute(?string $schemaName, array $request, $rootValue = null): ExecutionResult
    {
        $this->useExperimentalExecutor ? GraphQL::useExperimentalExecutor() : GraphQL::useReferenceExecutor();

        $executorArgumentsEvent = $this->preExecute(
            $this->getSchema($schemaName),
            $request[ParserInterface::PARAM_QUERY] ?? null,
            new ArrayObject(),
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

        $result = $this->postExecute($result, $executorArgumentsEvent);

        return $result;
    }

    /**
     * @param mixed $rootValue
     */
    private function preExecute(
        Schema $schema,
        string $requestString,
        ArrayObject $contextValue,
        $rootValue = null,
        ?array $variableValue = null,
        ?string $operationName = null
    ): ExecutorArgumentsEvent {
        // @phpstan-ignore-next-line (only for Symfony 4.4)
        $this->dispatcher->dispatch(new ExecutorContextEvent($contextValue), Events::EXECUTOR_CONTEXT);

        /** @var ExecutorArgumentsEvent $object */
        // @phpstan-ignore-next-line (only for Symfony 4.4)
        $object = $this->dispatcher->dispatch(
            /** @var ExtensibleSchema $schema */
            ExecutorArgumentsEvent::create($schema, $requestString, $contextValue, $rootValue, $variableValue, $operationName),
            Events::PRE_EXECUTOR
        );

        return $object;
    }

    private function postExecute(ExecutionResult $result, ExecutorArgumentsEvent $executorArguments): ExecutionResult
    {
        // @phpstan-ignore-next-line (only for Symfony 4.4)
        return $this->dispatcher->dispatch(
            new ExecutorResultEvent($result, $executorArguments),
            Events::POST_EXECUTOR
        )->getResult();
    }
}
