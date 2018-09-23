<?php

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;

class CustomScalarNode implements NodeInterface
{
    public static function toConfig(Node $node)
    {
        $mustOverride = [__CLASS__, 'mustOverrideConfig'];
        $config = [
            'description' => DescriptionNode::toConfig($node),
            'serialize' => $mustOverride,
            'parseValue' => $mustOverride,
            'parseLiteral' => $mustOverride,
        ];

        return [
            'type' => 'custom-scalar',
            'config' => $config,
        ];
    }

    public static function mustOverrideConfig()
    {
        throw new \RuntimeException('Config entry must be override with ResolverMap to be used.');
    }
}
