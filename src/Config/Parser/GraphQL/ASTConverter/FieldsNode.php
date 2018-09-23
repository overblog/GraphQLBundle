<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\ValueNode;

class FieldsNode implements NodeInterface
{
    public static function toConfig(Node $node, $property = 'fields'): array
    {
        $config = [];
        if (!empty($node->$property)) {
            foreach ($node->$property as $definition) {
                $fieldConfig = TypeNode::toConfig($definition) + DescriptionNode::toConfig($definition);

                if (!empty($definition->arguments)) {
                    $fieldConfig['args'] = self::toConfig($definition, 'arguments');
                }

                if (!empty($definition->defaultValue)) {
                    $fieldConfig['defaultValue'] = self::astValueNodeToConfig($definition->defaultValue);
                }

                $directiveConfig = DirectiveNode::toConfig($definition);
                if (isset($directiveConfig['deprecationReason'])) {
                    $fieldConfig['deprecationReason'] = $directiveConfig['deprecationReason'];
                }

                $config[$definition->name->value] = $fieldConfig;
            }
        }

        return $config;
    }

    private static function astValueNodeToConfig(ValueNode $valueNode)
    {
        $config = null;
        switch ($valueNode->kind) {
            case NodeKind::INT:
            case NodeKind::FLOAT:
            case NodeKind::STRING:
            case NodeKind::BOOLEAN:
            case NodeKind::ENUM:
                $config = $valueNode->value;
                break;

            case NodeKind::LST:
                $config = [];
                foreach ($valueNode->values as $node) {
                    $config[] = self::astValueNodeToConfig($node);
                }
                break;

            case NodeKind::NULL:
                $config = null;
                break;
        }

        return $config;
    }
}
