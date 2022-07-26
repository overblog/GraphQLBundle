<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class CustomScalarTypeDefinition extends TypeDefinition
{
    public const CONFIG_NAME = '_custom_scalar_config';

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
                ->append($this->descriptionSection())
                ->variableNode('scalarType')->end()
                ->variableNode('serialize')->end()
                ->variableNode('parseValue')->end()
                ->variableNode('parseLiteral')->end()
            ->end();

        return $node;
    }
}
