<?php

namespace Overblog\GraphQLBundle\Executor\Promise;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;

interface PromiseAdapterInterface extends PromiseAdapter
{
    /**
     * Synchronously wait when promise completes.
     *
     * @param Promise $promise
     *
     * @return ExecutionResult
     */
    public function wait(Promise $promise);
}
