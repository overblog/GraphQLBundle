<?php

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class CustomScalarTypeDefinition extends TypeDefinition
{
    public function getDefinition()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('_custom_scalar_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->variableNode('serialize')->isRequired()->end()
                ->variableNode('parseValue')->isRequired()->end()
                ->variableNode('parseLiteral')->isRequired()->end()
            ->end();

        return $node;
    }
}
