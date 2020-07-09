<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Executor;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface;
use RuntimeException;
use function func_get_args;
use function method_exists;
use function sprintf;

class Executor implements ExecutorInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(
        PromiseAdapter $promiseAdapter,
        Schema $schema,
        string $requestString,
        $rootValue = null,
        $contextValue = null,
        $variableValues = null,
        $operationName = null,
        ?callable $fieldResolver = null,
        ?array $validationRules = null
    ): ExecutionResult {
        if (!method_exists($promiseAdapter, 'wait')) {
            throw new RuntimeException(
                sprintf(
                    'PromiseAdapter should be an object instantiating "%s" or "%s" with a "wait" method.',
                    PromiseAdapterInterface::class,
                    PromiseAdapter::class
                )
            );
        }

        return $promiseAdapter->wait(GraphQL::promiseToExecute(...func_get_args()));
    }
}
