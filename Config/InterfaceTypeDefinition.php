<?php

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class InterfaceTypeDefinition extends TypeWithOutputFieldsDefinition
{
    public function getDefinition()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('_interface_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->outputFieldsSelection())
                ->append($this->resolveTypeSection())
                ->append($this->resolverMapSection())
                ->append($this->descriptionSection())
            ->end();

        $this->validateResolverMap($node);

        return $node;
    }
}
