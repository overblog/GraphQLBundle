<?php

namespace Overblog\GraphQLBundle\Config;

class UnionTypeDefinition extends TypeDefinition
{
    public function getDefinition()
    {
        $node = self::createNode('_union_config');

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
