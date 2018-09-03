<?php

declare(strict_types=1);

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
                ->append($this->descriptionSection())
                ->variableNode('scalarType')->end()
                ->variableNode('serialize')->end()
                ->variableNode('parseValue')->end()
                ->variableNode('parseLiteral')->end()
            ->end();

        return $node;
    }
}
