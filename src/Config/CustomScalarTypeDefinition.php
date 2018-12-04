<?php

namespace Overblog\GraphQLBundle\Config;

class CustomScalarTypeDefinition extends TypeDefinition
{
    public function getDefinition()
    {
        $node = self::createNode('_custom_scalar_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->descriptionSection())
                ->variableNode('scalarType')->end()
                ->variableNode('serialize')->end()
                ->variableNode('parseValue')->end()
                ->variableNode('parseLiteral')->end()
            ->end();

        return $node;
    }
}
