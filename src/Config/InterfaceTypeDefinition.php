<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class InterfaceTypeDefinition extends TypeWithOutputFieldsDefinition
{
    public function getDefinition(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
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
