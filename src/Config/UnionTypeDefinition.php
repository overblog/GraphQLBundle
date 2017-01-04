<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class UnionTypeDefinition extends TypeDefinition
{
    public function getDefinition()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('_union_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->arrayNode('types')
                    ->prototype('scalar')
                        ->info('One or more of object types.')
                    ->end()
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                ->end()
                ->append($this->resolveTypeSection())
                ->append($this->descriptionSection())
            ->end();

        return $node;
    }
}
