<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

use GraphQL\Executor\Promise\PromiseAdapter;

class SimplePromiseMutation
{
    /** @var PromiseAdapter */
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
