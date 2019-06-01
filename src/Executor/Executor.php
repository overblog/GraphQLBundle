<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Executor;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface;

class Executor implements ExecutorInterface
{
    /** @var PromiseAdapter */
    private $promiseAdapter;

    /**
     * {@inheritdoc}
     */
    public function execute(
        Schema $schema,
        string $requestString,
        $rootValue = null,
        $contextValue = null,
        $variableValues = null,
        $operationName = null,
        ?callable $fieldResolver = null,
        ?array $validationRules = null
    ): ExecutionResult {
        if ($this->promiseAdapter && !$this->promiseAdapter instanceof PromiseAdapterInterface && !\is_callable([$this->promiseAdapter, 'wait'])) {
            throw new \RuntimeException(
                \sprintf(
                    'PromiseAdapter should be an object instantiating "%s" or "%s" with a "wait" method.',
                    PromiseAdapterInterface::class,
                    PromiseAdapter::class
                )
            );
        }

        return $this->promiseAdapter->wait(GraphQL::promiseToExecute($this->promiseAdapter, ...\func_get_args()));
    }

    public function setPromiseAdapter(PromiseAdapter $promiseAdapter): void
    {
        $this->promiseAdapter = $promiseAdapter;
    }
}
