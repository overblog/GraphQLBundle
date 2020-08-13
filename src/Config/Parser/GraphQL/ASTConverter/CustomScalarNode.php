<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;
use RuntimeException;

class CustomScalarNode implements NodeInterface
{
    public static function toConfig(Node $node): array
    {
        $mustOverride = [__CLASS__, 'mustOverrideConfig'];
        $config = DescriptionNode::toConfig($node) + [
            'serialize' => $mustOverride,
            'parseValue' => $mustOverride,
            'parseLiteral' => $mustOverride,
        ];

        return [
            'type' => 'custom-scalar',
            'config' => $config,
        ];
    }

    public static function mustOverrideConfig(): void
    {
        throw new RuntimeException('Config entry must be override with ResolverMap to be used.');
    }
}
