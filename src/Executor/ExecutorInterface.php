<?php

namespace Overblog\GraphQLBundle\Executor;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Type\Schema;

interface ExecutorInterface
{
    /**
     * @param Schema      $schema
     * @param string      $requestString
     * @param null|array  $rootValue
     * @param null|array  $contextValue
     * @param null|array  $variableValues
     * @param null|string $operationName
     *
     * @return ExecutionResult|Promise
     */
    public function execute(Schema $schema, $requestString, $rootValue = null, $contextValue = null, $variableValues = null, $operationName = null);

    /**
     * @param PromiseAdapter|null $promiseAdapter
     */
    public function setPromiseAdapter(PromiseAdapter $promiseAdapter = null);

    /**
     * @param callable $fn
     */
    public function setDefaultFieldResolver(callable $fn);
}
