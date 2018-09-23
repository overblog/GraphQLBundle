<?php

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;

class UnionNode implements NodeInterface
{
    public static function toConfig(Node $node)
    {
        $config = ['description' => DescriptionNode::toConfig($node)];

        if (!empty($node->types)) {
            $types = [];
            foreach ($node->types as $type) {
                $types[] = TypeNode::astTypeNodeToString($type);
            }
            $config['types'] = $types;
        }

        return [
            'type' => 'union',
            'config' => $config,
        ];
    }
}
