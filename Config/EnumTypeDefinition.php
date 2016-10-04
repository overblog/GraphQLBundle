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

class EnumTypeDefinition extends TypeDefinition
{
    public function getDefinition()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('_enum_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->arrayNode('values')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifTrue(function ($v) {
                                return !is_null($v) && !is_array($v);
                            })
                            ->then(function ($v) {
                                return ['value' => $v];
                            })
                        ->end()
                        ->isRequired()
                        ->children()
                            ->scalarNode('value')->end()
                            ->append($this->descriptionSection())
                            ->append($this->deprecationReasonSelection())
                        ->end()
                    ->end()
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                ->end()
                ->append($this->descriptionSection())
            ->end();

        return $node;
    }
}
