<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class InterfaceTypeDefinition extends TypeWithOutputFieldsDefinition
{
    public function getDefinition(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode('_interface_config');
        $this->callbackNormalization($node, 'typeResolver', 'resolveType');

        /** @phpstan-ignore-next-line */
        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->outputFieldsSection())
                ->append($this->callbackSection('typeResolver', 'GraphQL type resolver'))
                ->append($this->descriptionSection())
                ->arrayNode('interfaces')
                    ->prototype('scalar')->info('One of internal or custom interface types.')->end()
                ->end()
            ->end();

        return $node;
    }
}
