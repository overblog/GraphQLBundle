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

class InputObjectTypeDefinition extends TypeDefinition
{
    public function getDefinition()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('_input_object_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->arrayNode('fields')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                        ->append($this->typeSelection(true))
                        ->append($this->descriptionSection())
                        ->append($this->defaultValueSection())
                    ->end()
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                ->end()
                ->append($this->descriptionSection())
            ->end();

        return $node;
    }
}
