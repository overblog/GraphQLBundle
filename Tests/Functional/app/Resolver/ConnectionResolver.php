<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\app\Resolver;

use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;

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

    public function friendsResolver($user, $args)
    {
        return ConnectionBuilder::connectionFromArray($user['friends'], $args);
    }

    public function resolveNode(Edge $edge)
    {
        return $this->allUsers[$edge->node];
    }

    public function resolveConnection()
    {
        return count($this->allUsers) - 1;
    }

    public function resolveQuery()
    {
        return $this->allUsers[0];
    }
}
