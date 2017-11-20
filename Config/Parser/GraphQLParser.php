<?php

namespace Overblog\GraphQLBundle\Config\Parser;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\Parser;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class GraphQLParser implements ParserInterface
{
    private static $instance;

    const DEFINITION_TYPE_MAPPING = [
        NodeKind::OBJECT_TYPE_DEFINITION => 'object',
        NodeKind::INTERFACE_TYPE_DEFINITION => 'interface',
        NodeKind::ENUM_TYPE_DEFINITION => 'enum',
        NodeKind::UNION_TYPE_DEFINITION => 'union',
        NodeKind::INPUT_OBJECT_TYPE_DEFINITION => 'input-object',
    ];

    /**
     * {@inheritdoc}
     */
    public static function parse(\SplFileInfo $file, ContainerBuilder $container)
    {
        $container->addResource(new FileResource($file->getRealPath()));
        $content = trim(file_get_contents($file->getPathname()));
        $typesConfig = [];

        // allow empty files
        if (empty($content)) {
            return [];
        }
        if (!self::$instance) {
            self::$instance = new static();
        }
        try {
            $ast = Parser::parse($content);

            /** @var Node $typeDef */
            foreach ($ast->definitions as $typeDef) {
                $typeConfig = self::$instance->typeDefinitionToConfig($typeDef);
                $typesConfig[$typeDef->name->value] = $typeConfig;
            }
        } catch (SyntaxError $e) {
            throw new InvalidArgumentException(sprintf('An error occurred while parsing the file "%s".', $file), $e->getCode(), $e);
        }

        return $typesConfig;
    }

    protected function typeDefinitionToConfig(Node $typeDef)
    {
        switch ($typeDef->kind) {
            case NodeKind::OBJECT_TYPE_DEFINITION:
            case NodeKind::INTERFACE_TYPE_DEFINITION:
            case NodeKind::INPUT_OBJECT_TYPE_DEFINITION:
            case NodeKind::ENUM_TYPE_DEFINITION:
            case NodeKind::UNION_TYPE_DEFINITION:
                $config = [];
                $this->addTypeFields($typeDef, $config);
                $this->addDescription($typeDef, $config);
                $this->addInterfaces($typeDef, $config);
                $this->addTypes($typeDef, $config);
                $this->addValues($typeDef, $config);

                return [
                    'type' => self::DEFINITION_TYPE_MAPPING[$typeDef->kind],
                    'config' => $config,
                ];

            default:
                throw new InvalidArgumentException(
                    sprintf(
                        '%s definition is not supported right now.',
                        preg_replace('@Definition$@', '', $typeDef->kind)
                    )
                );
        }
    }

    /**
     * @param Node  $typeDef
     * @param array $config
     */
    private function addTypeFields(Node $typeDef, array &$config)
    {
        if (!empty($typeDef->fields)) {
            $fields = [];
            /** @var FieldDefinitionNode|InputValueDefinitionNode $fieldDef */
            foreach ($typeDef->fields as $fieldDef) {
                $fieldName = $fieldDef->name->value;
                $fields[$fieldName] = [];
                $this->addType($fieldDef, $fields[$fieldName]);
                $this->addDescription($fieldDef, $fields[$fieldName]);
                $this->addDefaultValue($fieldDef, $fields[$fieldName]);
                $this->addFieldArguments($fieldDef, $fields[$fieldName]);
            }
            $config['fields'] = $fields;
        }
    }

    /**
     * @param Node  $fieldDef
     * @param array $fieldConf
     */
    private function addFieldArguments(Node $fieldDef, array &$fieldConf)
    {
        if (!empty($fieldDef->arguments)) {
            $arguments = [];
            /** @var InputValueDefinitionNode $definition */
            foreach ($fieldDef->arguments as $definition) {
                $name = $definition->name->value;
                $arguments[$name] = [];
                $this->addType($definition, $arguments[$name]);
                $this->addDescription($definition, $arguments[$name]);
                $this->addDefaultValue($definition, $arguments[$name]);
            }
            $fieldConf['arguments'] = $arguments;
        }
    }

    /**
     * @param Node  $typeDef
     * @param array $config
     */
    private function addInterfaces(Node $typeDef, array &$config)
    {
        if (!empty($typeDef->interfaces)) {
            $interfaces = [];
            foreach ($typeDef->interfaces as $interface) {
                $interfaces[] = $this->astTypeNodeToString($interface);
            }
            $config['interfaces'] = $interfaces;
        }
    }

    /**
     * @param Node  $typeDef
     * @param array $config
     */
    private function addTypes(Node $typeDef, array &$config)
    {
        if (!empty($typeDef->types)) {
            $types = [];
            foreach ($typeDef->types as $type) {
                $types[] = $this->astTypeNodeToString($type);
            }
            $config['types'] = $types;
        }
    }

    /**
     * @param Node  $typeDef
     * @param array $config
     */
    private function addValues(Node $typeDef, array &$config)
    {
        if (!empty($typeDef->values)) {
            $values = [];
            foreach ($typeDef->values as $value) {
                $values[] = ['value' => $value->name->value];
            }
            $config['values'] = $values;
        }
    }

    /**
     * @param Node  $definition
     * @param array $config
     */
    private function addType(Node $definition, array &$config)
    {
        if (!empty($definition->type)) {
            $config['type'] = $this->astTypeNodeToString($definition->type);
        }
    }

    /**
     * @param Node  $definition
     * @param array $config
     */
    private function addDescription(Node $definition, array &$config)
    {
        if (
            !empty($definition->description)
            && $description = $this->cleanAstDescription($definition->description)
        ) {
            $config['description'] = $description;
        }
    }

    /**
     * @param InputValueDefinitionNode|FieldDefinitionNode $definition
     * @param array                                        $config
     */
    private function addDefaultValue($definition, array &$config)
    {
        if (!empty($definition->defaultValue)) {
            $config['defaultValue'] = $this->astValueNodeToConfig($definition->defaultValue);
        }
    }

    private function astTypeNodeToString(TypeNode $typeNode)
    {
        $type = '';
        switch ($typeNode->kind) {
            case NodeKind::NAMED_TYPE:
                $type = $typeNode->name->value;
                break;

            case NodeKind::NON_NULL_TYPE:
                $type = $this->astTypeNodeToString($typeNode->type).'!';
                break;

            case NodeKind::LIST_TYPE:
                $type = '['.$this->astTypeNodeToString($typeNode->type).']';
                break;
        }

        return $type;
    }

    private function astValueNodeToConfig(ValueNode $valueNode)
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
                    $config[] = $this->astValueNodeToConfig($node);
                }
                break;

            case NodeKind::NULL:
                $config = null;
                break;
        }

        return $config;
    }

    private function cleanAstDescription($description)
    {
        $description = trim($description);

        return empty($description) ? null : $description;
    }
}
