<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ArgsInterface;

class BackwardConnectionArgs implements ArgsInterface
{
    /**
     * @param array $config
     *
     * @return array
     */
    public function toArgsDefinition(array $config)
    {
        return [
            'before' => [
                'type' => Type::string(),
            ],
            'last' => [
                'type' => Type::int(),
            ],
        ];
    }
}
