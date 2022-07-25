<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;

class ObjectNode implements NodeInterface
{
    protected const TYPENAME = 'object';

    public static function toConfig(Node $node): array
    {
        return [
            'type' => static::TYPENAME,
            'config' => static::parseConfig($node),
        ];
    }

    /**
     * @param ObjectTypeDefinitionNode $node
     *
     * @return array<string,mixed>
     */
    protected static function parseConfig(Node $node): array
    {
        $config = DescriptionNode::toConfig($node) + [
            'fields' => FieldsNode::toConfig($node),
        ];

        if (!empty($node->interfaces)) {
            $interfaces = [];
            foreach ($node->interfaces as $interface) {
                $interfaces[] = TypeNode::astTypeNodeToString($interface);
            }
            $config['interfaces'] = $interfaces;
        }

        return $config;
    }
}
