<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Config;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

abstract class TypeWithOutputFieldsDefinition extends TypeDefinition
{
    /**
     * @var MappingInterface[]
     */
    private static $argsBuilderClassMap = [
        'ForwardConnectionArgs' => 'Overblog\GraphQLBundle\Relay\Connection\ForwardConnectionArgsDefinition',
        'BackwardConnectionArgs' => 'Overblog\GraphQLBundle\Relay\Connection\BackwardConnectionArgsDefinition',
        'ConnectionArgs' => 'Overblog\GraphQLBundle\Relay\Connection\ConnectionArgsDefinition',
    ];

    /**
     * @var MappingInterface[]
     */
    private static $fieldBuilderClassMap = [
        'Mutation' => 'Overblog\GraphQLBundle\Relay\Mutation\MutationFieldDefinition',
        'GlobalId' => 'Overblog\GraphQLBundle\Relay\Node\GlobalIdFieldDefinition',
        'Node' => 'Overblog\GraphQLBundle\Relay\Node\NodeFieldDefinition',
        'PluralIdentifyingRoot' => 'Overblog\GraphQLBundle\Relay\Node\PluralIdentifyingRootFieldDefinition',
    ];

    public static function addArgsBuilderClass($name, $argBuilderClass)
    {
        self::checkBuilderClass($argBuilderClass, 'args');

        self::$argsBuilderClassMap[$name] = $argBuilderClass;
    }

    public static function addFieldBuilderClass($name, $fieldBuilderClass)
    {
        self::checkBuilderClass($fieldBuilderClass, 'field');

        self::$fieldBuilderClassMap[$name] = $fieldBuilderClass;
    }

    protected static function checkBuilderClass($builderClass, $type)
    {
        $interface = 'Overblog\\GraphQLBundle\\Definition\\Builder\\MappingInterface';

        if (!is_string($builderClass)) {
            throw new \InvalidArgumentException(
                sprintf('%s builder class should be string, but "%s" given.', ucfirst($type), gettype($builderClass))
            );
        }

        if (!class_exists($builderClass)) {
            throw new \InvalidArgumentException(
                sprintf('%s builder class "%s" not found.', ucfirst($type), $builderClass)
            );
        }

        if (!is_subclass_of($builderClass, $interface)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s builder class should be instance of "%s", but "%s" given.',
                    ucfirst($type),
                    $interface,
                    $builderClass
                )
            );
        }
    }

    /**
     * @param $name
     *
     * @return MappingInterface|null
     */
    protected function getArgsBuilder($name)
    {
        static $builders = [];
        if (isset($builders[$name])) {
            return $builders[$name];
        }

        if (isset(self::$argsBuilderClassMap[$name])) {
            return $builders[$name] = new self::$argsBuilderClassMap[$name]();
        }
    }

    /**
     * @param $name
     *
     * @return MappingInterface|null
     */
    protected function getFieldBuilder($name)
    {
        static $builders = [];
        if (isset($builders[$name])) {
            return $builders[$name];
        }

        if (isset(self::$fieldBuilderClassMap[$name])) {
            return $builders[$name] = new self::$fieldBuilderClassMap[$name]();
        }
    }

    protected function outputFieldsSelection($name, $withAccess = false)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);
        $node
            ->isRequired()
            ->requiresAtLeastOneElement();

        /* @var ArrayNodeDefinition $prototype */
        $prototype = $node->useAttributeAsKey('name', false)->prototype('array');

        $prototype
            // build args if argsBuilder exists
            ->beforeNormalization()
                ->ifTrue(function ($field) {
                    return isset($field['argsBuilder']);
                })
                ->then(function ($field) {
                    $argsBuilderName = null;

                    if (is_string($field['argsBuilder'])) {
                        $argsBuilderName = $field['argsBuilder'];
                    } elseif (isset($field['argsBuilder']['builder']) && is_string($field['argsBuilder']['builder'])) {
                        $argsBuilderName = $field['argsBuilder']['builder'];
                    }

                    if ($argsBuilderName) {
                        if (!($argsBuilder = $this->getArgsBuilder($argsBuilderName))) {
                            throw new InvalidConfigurationException(sprintf('Args builder "%s" not found.', $argsBuilder));
                        }

                        $args = $argsBuilder->toMappingDefinition([]);

                        $field['args'] = isset($field['args']) && is_array($field['args']) ? array_merge($args, $field['args']) : $args;
                    }

                    unset($field['argsBuilder']);

                    return $field;
                })
            ->end()
            // build field if builder exists
            ->beforeNormalization()
                ->always(function ($field) {
                    $fieldBuilderName = null;

                    if (isset($field['builder']) && is_string($field['builder'])) {
                        $fieldBuilderName = $field['builder'];
                        unset($field['builder']);
                    } elseif (is_string($field)) {
                        $fieldBuilderName = $field;
                    }

                    $builderConfig = [];
                    if (isset($field['builderConfig'])) {
                        if (is_array($field['builderConfig'])) {
                            $builderConfig = $field['builderConfig'];
                        }
                        unset($field['builderConfig']);
                    }

                    if ($fieldBuilderName) {
                        if (!($fieldBuilder = $this->getFieldBuilder($fieldBuilderName))) {
                            throw new InvalidConfigurationException(sprintf('Field builder "%s" not found.', $fieldBuilderName));
                        }
                        $buildField = $fieldBuilder->toMappingDefinition($builderConfig);
                        $field = is_array($field) ? array_merge($buildField, $field) : $buildField;
                    }

                    return $field;
                })
            ->end();

        $prototype
            ->children()
                ->append($this->typeSelection())
                ->arrayNode('args')
                    ->info('Array of possible type arguments. Each entry is expected to be an array with following keys: name (string), type')
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                        ->children()
                            ->append($this->typeSelection(true))
                            ->scalarNode('description')->end()
                            ->append($this->defaultValueSection())
                        ->end()
                    ->end()
                ->end()
                ->variableNode('resolve')
                    ->info('Value resolver (expression language can be use here)')
                ->end()
                ->append($this->descriptionSection())
                ->append($this->deprecationReasonSelection())
                ->variableNode('access')
                    ->info('Access control to field (expression language can be use here)')
                ->end()
                ->variableNode('complexity')
                    ->info('Custom complexity calculator.')
                ->end()
                ->variableNode('map')->end()
            ->end();

        if ($withAccess) {
            $prototype
                ->children()
                    ->variableNode('access')
                        ->info('Access control to field (expression language can be use here)')
                    ->end()
                ->end();
        }

        return $node;
    }
}
