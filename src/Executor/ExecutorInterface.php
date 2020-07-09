<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Executor;

use ArrayObject;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Type\Schema;

interface ExecutorInterface
{
    /**
     * @param mixed                  $rootValue
     * @param ArrayObject|array|null $contextValue
     * @param array|null             $variableValues
     * @param string|null            $operationName
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
    ): ExecutionResult;
}
