<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use function trim;

class DescriptionNode implements NodeInterface
{
    public static function toConfig(Node $node): array
    {
        return ['description' => self::cleanAstDescription($node->description)];
    }

    private static function cleanAstDescription(?StringValueNode $description): ?string
    {
        if (null === $description) {
            return null;
        }

        $description = trim($description->value);

        return empty($description) ? null : $description;
    }
}
