<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Executor\Promise;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;

interface PromiseAdapterInterface extends PromiseAdapter
{
    /**
     * Synchronously wait when promise completes.
     */
    public function wait(Promise $promise): ?ExecutionResult;
}
