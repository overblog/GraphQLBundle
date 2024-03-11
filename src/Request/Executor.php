<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Request;

use ArrayObject;
use Closure;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\PromiseAdapter;
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
use Symfony\Contracts\Service\ResetInterface;
use function array_keys;
use function sprintf;

class Executor implements ResetInterface
{
    public const PROMISE_ADAPTER_SERVICE_ID = 'overblog_graphql.promise_adapter';

    /**
     * @var array<Closure>
     */
    private array $schemaBuilders = [];
    /**
     * @var array<Schema>
     */
    private array $schemas = [];
    private EventDispatcherInterface $dispatcher;
    private PromiseAdapter $promiseAdapter;
    private ExecutorInterface $executor;

    /**
     * @var callable|null
     */
    private $defaultFieldResolver;

    public function __construct(
        ExecutorInterface $executor,
        PromiseAdapter $promiseAdapter,
        EventDispatcherInterface $dispatcher,
        callable $defaultFieldResolver = null
    ) {
        $this->executor = $executor;
        $this->promiseAdapter = $promiseAdapter;
        $this->dispatcher = $dispatcher;
        $this->defaultFieldResolver = $defaultFieldResolver;
    }

    public function setExecutor(ExecutorInterface $executor): self
    {
        $this->executor = $executor;

        return $this;
    }

    public function addSchemaBuilder(string $name, Closure $builder): self
    {
        $this->schemaBuilders[$name] = $builder;

        return $this;
    }

    public function addSchema(string $name, Schema $schema): self
    {
        $this->schemas[$name] = $schema;

        return $this;
    }

    public function getSchema(string $name = null): Schema
    {
        if (empty($this->schemaBuilders) && empty($this->schemas)) {
            throw new RuntimeException('At least one schema should be declare.');
        }

        if (null === $name) {
            $name = isset($this->schemas['default']) ? 'default' : array_key_first($this->schemas);
        }

        if (null === $name) {
            $name = isset($this->schemaBuilders['default']) ? 'default' : array_key_first($this->schemaBuilders);
        }

        if (isset($this->schemas[$name])) {
            $schema = $this->schemas[$name];
        } elseif (isset($this->schemaBuilders[$name])) {
            $schema = call_user_func($this->schemaBuilders[$name]);

            $this->addSchema((string) $name, $schema);
        } else {
            throw new NotFoundHttpException(sprintf('Could not find "%s" schema.', $name));
        }

        return $schema;
    }

    public function reset(): void
    {
        // Remove only ExtensibleSchema and isResettable
        $this->schemas = array_filter(
            $this->schemas,
            fn (Schema $schema) => $schema instanceof ExtensibleSchema && !$schema->isResettable()
        );
    }

    public function getSchemasNames(): array
    {
        return array_merge(array_keys($this->schemaBuilders), array_keys($this->schemas));
    }

    public function setMaxQueryDepth(int $maxQueryDepth): void
    {
        /** @var QueryDepth $queryDepth */
        $queryDepth = DocumentValidator::getRule(QueryDepth::class);
        $queryDepth->setMaxQueryDepth($maxQueryDepth);
    }

    public function setMaxQueryComplexity(int $maxQueryComplexity): void
    {
        /** @var QueryComplexity $queryComplexity */
        $queryComplexity = DocumentValidator::getRule(QueryComplexity::class);
        $queryComplexity->setMaxQueryComplexity($maxQueryComplexity);
    }

    public function enableIntrospectionQuery(): void
    {
        DocumentValidator::addRule(new DisableIntrospection(DisableIntrospection::DISABLED));
    }

    public function disableIntrospectionQuery(): void
    {
        DocumentValidator::addRule(new DisableIntrospection(DisableIntrospection::ENABLED));
    }

    /**
     * @param array|ArrayObject|object|null $rootValue
     */
    public function execute(?string $schemaName, array $request, $rootValue = null): ExecutionResult
    {
        $schema = $this->getSchema($schemaName);
        /** @var string $schemaName */
        $schemaName = array_search($schema, $this->schemas);

        $executorArgumentsEvent = $this->preExecute(
            $schemaName,
            $schema,
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
        string $schemaName,
        Schema $schema,
        string $requestString,
        ArrayObject $contextValue,
        $rootValue = null,
        array $variableValue = null,
        string $operationName = null
    ): ExecutorArgumentsEvent {
        // @phpstan-ignore-next-line (only for Symfony 4.4)
        $this->dispatcher->dispatch(new ExecutorContextEvent($contextValue), Events::EXECUTOR_CONTEXT);

        /** @var ExecutorArgumentsEvent $object */
        // @phpstan-ignore-next-line (only for Symfony 4.4)
        $object = $this->dispatcher->dispatch(
            // @phpstan-ignore-next-line
            ExecutorArgumentsEvent::create($schemaName, $schema, $requestString, $contextValue, $rootValue, $variableValue, $operationName),
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
