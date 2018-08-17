<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter;
use GraphQL\Executor\Promise\PromiseAdapter;
use Overblog\GraphQLBundle\Executor\Promise\Adapter\ReactPromiseAdapter;
use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use React\Promise\Promise;

class ConnectionResolver
{
    private $allUsers = [
        [
            'name' => 'Dan',
            'friends' => [1, 2, 3, 4],
        ],
        [
            'name' => 'Nick',
            'friends' => [0, 2, 3, 4],
        ],
        [
            'name' => 'Lee',
            'friends' => [0, 1, 3, 4],
        ],
        [
            'name' => 'Joe',
            'friends' => [0, 1, 2, 4],
        ],
        [
            'name' => 'Tim',
            'friends' => [0, 1, 2, 3],
        ],
    ];

    /**
     * @var PromiseAdapter
     */
    private $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function friendsResolver($user, $args)
    {
        return $this->promiseAdapter->create(function (callable $resolve) use ($user, $args) {
            return $resolve(ConnectionBuilder::connectionFromArray($user['friends'], $args));
        });
    }

    public function resolveNode(Edge $edge)
    {
        return $this->promiseAdapter->create(function (callable $resolve) use ($edge) {
            return $resolve(isset($this->allUsers[$edge->node]) ? $this->allUsers[$edge->node] : null);
        });
    }

    public function resolveConnection()
    {
        return $this->promiseAdapter->create(function (callable $resolve) {
            return $resolve(count($this->allUsers) - 1);
        });
    }

    public function resolveQuery()
    {
        if ($this->promiseAdapter instanceof SyncPromiseAdapter) {
            return new Deferred(function () {
                return $this->allUsers[0];
            });
        } elseif ($this->promiseAdapter instanceof ReactPromiseAdapter) {
            return new Promise(function (callable $resolve) {
                return $resolve($this->allUsers[0]);
            });
        }

        return $this->allUsers[0];
    }
}
