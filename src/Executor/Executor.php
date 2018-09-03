<?php

declare(strict_types=1);

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
    public function execute(Schema $schema, string $requestString, array $rootValue = null, $contextValue = null, $variableValues = null, $operationName = null)
    {
        $args = \func_get_args();
        \array_unshift($args, $this->promiseAdapter);

        return \call_user_func_array([GraphQL::class, 'promiseToExecute'], $args);
    }

    /**
     * {@inheritdoc}
     */
    public function setPromiseAdapter(PromiseAdapter $promiseAdapter): void
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultFieldResolver(callable $fn): void
    {
        \call_user_func_array([GraphQL::class, 'setDefaultFieldResolver'], \func_get_args());
    }
}
