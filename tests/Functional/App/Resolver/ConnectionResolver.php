<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter;
use GraphQL\Executor\Promise\Promise as GraphQLPromise;
use GraphQL\Executor\Promise\PromiseAdapter;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Executor\Promise\Adapter\ReactPromiseAdapter;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use React\Promise\Promise as ReactPromise;
use function count;

class ConnectionResolver
{
    private array $allUsers = [
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

    private PromiseAdapter $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function friendsResolver(array $user, ArgumentInterface $args): GraphQLPromise
    {
        return $this->promiseAdapter->create(function (callable $resolve) use ($user, $args) {
            return $resolve((new ConnectionBuilder())->connectionFromArray($user['friends'], $args));
        });
    }

    public function resolveNode(Edge $edge): GraphQLPromise
    {
        return $this->promiseAdapter->create(function (callable $resolve) use ($edge) {
            return $resolve(isset($this->allUsers[$edge->getNode()]) ? $this->allUsers[$edge->getNode()] : null);
        });
    }

    public function resolveConnection(): GraphQLPromise
    {
        return $this->promiseAdapter->create(function (callable $resolve) {
            return $resolve(count($this->allUsers) - 1);
        });
    }

    /**
     * @return array|Deferred|mixed|ReactPromise
     */
    public function resolveQuery()
    {
        if ($this->promiseAdapter instanceof SyncPromiseAdapter) {
            return new Deferred(function () {
                return $this->allUsers[0];
            });
        } elseif ($this->promiseAdapter instanceof ReactPromiseAdapter) {
            return new ReactPromise(function (callable $resolve) {
                return $resolve($this->allUsers[0]);
            });
        }

        return $this->allUsers[0];
    }

    /**
     * @param mixed $value
     */
    public function resolvePromiseFullFilled($value): GraphQLPromise
    {
        return $this->promiseAdapter->createFulfilled($value);
    }
}
