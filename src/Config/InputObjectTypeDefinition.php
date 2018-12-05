<?php

namespace Overblog\GraphQLBundle\Config;

class InputObjectTypeDefinition extends TypeDefinition
{
    public function getDefinition()
    {
        $node = self::createNode('_input_object_config');

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
