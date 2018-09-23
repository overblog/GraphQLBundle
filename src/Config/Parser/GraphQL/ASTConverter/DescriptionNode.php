<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;

class DescriptionNode implements NodeInterface
{
    public static function toConfig(Node $node): array
    {
        return ['description' => self::cleanAstDescription($node->description)];
    }

    private static function cleanAstDescription($description)
    {
        if (null === $description) {
            return null;
        }

        if (\property_exists($description, 'value')) {
            $description = $description->value;
        }
        $description = \trim($description);

        return empty($description) ? null : $description;
    }
}
