<?php

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;

class EnumNode implements NodeInterface
{
    public static function toConfig(Node $node)
    {
        $config = [
            'description' => DescriptionNode::toConfig($node),
        ];

        $values = [];
        foreach ($node->values as $value) {
            $values[$value->name->value] = [
                'description' => DescriptionNode::toConfig($node),
                'value' => $value->name->value,
            ];

            $directiveConfig = DirectiveNode::toConfig($value);
            if (isset($directiveConfig['deprecationReason'])) {
                $values[$value->name->value]['deprecationReason'] = $directiveConfig['deprecationReason'];
            }
        }
        $config['values'] = $values;

        return [
            'type' => 'enum',
            'config' => $config,
        ];
    }
}
