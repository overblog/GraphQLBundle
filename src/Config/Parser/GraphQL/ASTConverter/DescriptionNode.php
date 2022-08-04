<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\TypeExtensionNode;
use function trim;

class DescriptionNode implements NodeInterface
{
    public static function toConfig(Node $node): array
    {
        if ($node instanceof TypeExtensionNode) {
            return [];
        }

        return ['description' => self::cleanAstDescription($node->description)];
    }

    private static function cleanAstDescription(?StringValueNode $description): ?string
    {
        if (null === $description) {
            return null;
        }

        return trim($description->value) ?: null;
    }
}
