<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

class InterfaceTypeDefinition extends TypeWithOutputFieldsDefinition
{
    public function getDefinition()
    {
        $node = self::createNode('_interface_config');

        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->outputFieldsSection())
                ->append($this->resolveTypeSection())
                ->append($this->descriptionSection())
            ->end();

        return $node;
    }
}
