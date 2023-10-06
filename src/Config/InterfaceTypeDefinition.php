<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class InterfaceTypeDefinition extends TypeWithOutputFieldsDefinition
{
    public const CONFIG_NAME = '_interface_config';

    public static function getName(): string
    {
        return static::CONFIG_NAME;
    }

    public function getDefinition(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode(static::CONFIG_NAME);

        /** @phpstan-ignore-next-line */
        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->outputFieldsSection())
                ->append($this->resolveTypeSection())
                ->append($this->descriptionSection())
                ->arrayNode('interfaces')
                    ->prototype('scalar')->info('One of internal or custom interface types.')->end()
                ->end()
            ->end();

        return $node;
    }
}
