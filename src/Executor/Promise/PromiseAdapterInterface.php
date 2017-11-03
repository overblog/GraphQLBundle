<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
