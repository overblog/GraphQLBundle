<?php

namespace Overblog\GraphQLBundle\Executor;

use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;

class Executor implements ExecutorInterface
{
    /** @var PromiseAdapter */
    private $promiseAdapter;

    /**
     * {@inheritdoc}
     */
    public function execute(Schema $schema, $requestString, $rootValue = null, $contextValue = null, $variableValues = null, $operationName = null)
    {
        $args = \func_get_args();
        \array_unshift($args, $this->promiseAdapter);

        return \call_user_func_array([GraphQL::class, 'promiseToExecute'], $args);
    }

    /**
     * {@inheritdoc}
     */
    public function setPromiseAdapter(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultFieldResolver(callable $fn)
    {
        \call_user_func_array([GraphQL::class, 'setDefaultFieldResolver'], \func_get_args());
    }
}
