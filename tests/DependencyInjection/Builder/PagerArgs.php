<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class PagerArgs implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        return [
            'limit' => [
                'type' => 'Int!',
                'defaultValue' => 20,
            ],
            'offset' => [
                'type' => 'Int!',
                'defaultValue' => 0,
            ],
        ];
    }
}
