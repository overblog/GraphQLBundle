<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

use GraphQL\Executor\Promise\PromiseAdapter;

class SimplePromiseMutation
{
    /**
     * @var PromiseAdapter
     */
    private $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function mutate()
    {
        return $this->promiseAdapter->createFulfilled(['result' => 1]);
    }
}
