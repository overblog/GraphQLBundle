<?php

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;

class EnumNode implements NodeInterface
{
    public static function toConfig(Node $node)
    {
        $values = [];

        foreach ($node->values as $value) {
            $values[$value->name->value] = [
                'description' => DescriptionNode::toConfig($value),
                'value' => $value->name->value,
            ];

            $directiveConfig = DirectiveNode::toConfig($value);

            if (isset($directiveConfig['deprecationReason'])) {
                $values[$value->name->value]['deprecationReason'] = $directiveConfig['deprecationReason'];
            }
        }

        return [
            'type' => 'enum',
            'config' => [
                'description' => DescriptionNode::toConfig($node),
                'values' => $values,
            ],
        ];
    }
}
