<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\GraphQL\ASTConverter;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Utils\AST;

class FieldsNode implements NodeInterface
{
    public static function toConfig(Node $node, string $property = 'fields'): array
    {
        $config = [];
        if (!empty($node->$property)) {
            foreach ($node->$property as $definition) {
                $fieldConfig = TypeNode::toConfig($definition) + DescriptionNode::toConfig($definition);

                if (!empty($definition->arguments)) {
                    $fieldConfig['args'] = static::toConfig($definition, 'arguments');
                }

                if (!empty($definition->defaultValue)) {
                    $fieldConfig['defaultValue'] = AST::valueFromASTUntyped($definition->defaultValue);
                }

                $directiveConfig = DirectiveNode::toConfig($definition);
                if (isset($directiveConfig['deprecationReason'])) {
                    $fieldConfig['deprecationReason'] = $directiveConfig['deprecationReason'];
                }

                $config[$definition->name->value] = static::extendFieldConfig($fieldConfig, $definition);
            }
        }

        return $config;
    }

    /**
     * @param array<string,mixed> $fieldConfig
     * @param FieldDefinitionNode|InputValueDefinitionNode $fieldDefinition
     *
     * @return array<string,mixed>
     */
    protected static function extendFieldConfig(array $fieldConfig, Node $fieldDefinition): array
    {
        return $fieldConfig;
    }
}
