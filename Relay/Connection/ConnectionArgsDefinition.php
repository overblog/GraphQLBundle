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
use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

final class ConnectionArgsDefinition implements MappingInterface
{
    /**
     * @param array $config
     *
     * @return array
     */
    public function toMappingDefinition(array $config)
    {
        return [
            'after' => [
                'type' => 'String',
            ],
            'first' => [
                'type' => 'Int',
            ],
            'before' => [
                'type' => 'String',
            ],
            'last' => [
                'type' => 'Int',
            ],
        ];
    }
}
