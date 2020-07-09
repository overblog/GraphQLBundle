<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;

class SimplePromiseMutation
{
    private PromiseAdapter $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function mutate(): Promise
    {
        return $this->promiseAdapter->createFulfilled((object) ['result' => 1]);
    }
}
