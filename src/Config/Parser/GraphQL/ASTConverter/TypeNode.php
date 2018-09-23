<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;

class TypeNode implements NodeInterface
{
    public static function toConfig(Node $node): array
    {
        return ['type' => self::astTypeNodeToString($node->type)];
    }

    public static function astTypeNodeToString(\GraphQL\Language\AST\TypeNode $typeNode): string
    {
        $type = '';
        switch ($typeNode->kind) {
            case NodeKind::NAMED_TYPE:
                $type = $typeNode->name->value;
                break;

            case NodeKind::NON_NULL_TYPE:
                $type = self::astTypeNodeToString($typeNode->type).'!';
                break;

            case NodeKind::LIST_TYPE:
                $type = '['.self::astTypeNodeToString($typeNode->type).']';
                break;
        }

        return $type;
    }
}
