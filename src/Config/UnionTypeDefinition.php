<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class UnionTypeDefinition extends TypeDefinition
{
    public function getDefinition(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode('_union_config');
        $this->callbackNormalization($node, 'typeResolver', 'resolveType');

        /** @phpstan-ignore-next-line */
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
                ->append($this->callbackSection('typeResolver', 'GraphQL type resolver'))
                ->append($this->descriptionSection())
            ->end();

        return $node;
    }
}
