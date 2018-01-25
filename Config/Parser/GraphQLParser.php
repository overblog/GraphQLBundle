<?php

namespace Overblog\GraphQLBundle\Config\Parser;

use GraphQL\Language\AST\DefinitionNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InputValueDefinitionNode;
use GraphQL\Language\AST\NameNode;
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
    /** @var self */
    private static $parser;

    const DEFINITION_TYPE_MAPPING = [
        NodeKind::OBJECT_TYPE_DEFINITION => 'object',
        NodeKind::INTERFACE_TYPE_DEFINITION => 'interface',
        NodeKind::ENUM_TYPE_DEFINITION => 'enum',
        NodeKind::UNION_TYPE_DEFINITION => 'union',
        NodeKind::INPUT_OBJECT_TYPE_DEFINITION => 'input-object',
        NodeKind::SCALAR_TYPE_DEFINITION => 'custom-scalar',
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
        if (!self::$parser) {
            self::$parser = new static();
        }
        try {
            $ast = Parser::parse($content);
        } catch (\Exception $e) {
            throw new InvalidArgumentException(sprintf('An error occurred while parsing the file "%s".', $file), $e->getCode(), $e);
        }

        foreach ($ast->definitions as $typeDef) {
            if (isset($typeDef->name) && $typeDef->name instanceof NameNode) {
                $typeConfig = self::$parser->typeDefinitionToConfig($typeDef);
                $typesConfig[$typeDef->name->value] = $typeConfig;
            } else {
                self::throwUnsupportedDefinitionNode($typeDef);
            }
        }

        return $typesConfig;
    }

    public static function mustOverrideConfig()
    {
        throw new \RuntimeException('Config entry must be override with ResolverMap to be used.');
    }

    protected function typeDefinitionToConfig(DefinitionNode $typeDef)
    {
        if (isset($typeDef->kind)) {
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

                case NodeKind::SCALAR_TYPE_DEFINITION:
                    $mustOverride = [__CLASS__, 'mustOverrideConfig'];
                    $config = [
                        'serialize' => $mustOverride,
                        'parseValue' => $mustOverride,
                        'parseLiteral' => $mustOverride,
                    ];
                    $this->addDescription($typeDef, $config);

                    return [
                        'type' => self::DEFINITION_TYPE_MAPPING[$typeDef->kind],
                        'config' => $config,
                    ];
                    break;

                default:
                    self::throwUnsupportedDefinitionNode($typeDef);
            }
        }

        self::throwUnsupportedDefinitionNode($typeDef);
    }

    private static function throwUnsupportedDefinitionNode(DefinitionNode $typeDef)
    {
        $path = explode('\\', get_class($typeDef));
        throw new InvalidArgumentException(
            sprintf(
                '%s definition is not supported right now.',
                preg_replace('@DefinitionNode$@', '', array_pop($path))
            )
        );
    }

    /**
     * @param DefinitionNode  $typeDef
     * @param array           $config
     */
    private function addTypeFields(DefinitionNode $typeDef, array &$config)
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
     * @param array                     $fieldConf
     */
    private function addFieldArguments(Node $fieldDef, array &$fieldConf)
    {
        if (!empty($fieldDef->arguments)) {
            $arguments = [];
            foreach ($fieldDef->arguments as $definition) {
                $name = $definition->name->value;
                $arguments[$name] = [];
                $this->addType($definition, $arguments[$name]);
                $this->addDescription($definition, $arguments[$name]);
                $this->addDefaultValue($definition, $arguments[$name]);
            }
            $fieldConf['args'] = $arguments;
        }
    }

    /**
     * @param DefinitionNode  $typeDef
     * @param array           $config
     */
    private function addInterfaces(DefinitionNode $typeDef, array &$config)
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
     * @param DefinitionNode  $typeDef
     * @param array           $config
     */
    private function addTypes(DefinitionNode $typeDef, array &$config)
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
     * @param DefinitionNode  $typeDef
     * @param array $config
     */
    private function addValues(DefinitionNode $typeDef, array &$config)
    {
        if (!empty($typeDef->values)) {
            $values = [];
            foreach ($typeDef->values as $value) {
                $values[$value->name->value] = ['value' => $value->name->value];
                $this->addDescription($value, $values[$value->name->value]);
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
     * @param array           $config
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
     * @param Node $definition
     * @param array                                        $config
     */
    private function addDefaultValue(Node $definition, array &$config)
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
