<?php

namespace Overblog\GraphQLBundle\Config;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\Relay\Connection\BackwardConnectionArgsDefinition;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionArgsDefinition;
use Overblog\GraphQLBundle\Relay\Connection\ForwardConnectionArgsDefinition;
use Overblog\GraphQLBundle\Relay\Mutation\MutationFieldDefinition;
use Overblog\GraphQLBundle\Relay\Node\GlobalIdFieldDefinition;
use Overblog\GraphQLBundle\Relay\Node\NodeFieldDefinition;
use Overblog\GraphQLBundle\Relay\Node\PluralIdentifyingRootFieldDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

abstract class TypeWithOutputFieldsDefinition extends TypeDefinition
{
    const BUILDER_FIELD_TYPE = 'field';
    const BUILDER_ARGS_TYPE = 'args';

    /** @var MappingInterface[] */
    private static $argsBuilderClassMap = [
        'Relay::ForwardConnection' => ForwardConnectionArgsDefinition::class,
        'Relay::BackwardConnection' => BackwardConnectionArgsDefinition::class,
        'Relay::Connection' => ConnectionArgsDefinition::class,
    ];

    /** @var MappingInterface[] */
    private static $fieldBuilderClassMap = [
        'Relay::Mutation' => MutationFieldDefinition::class,
        'Relay::GlobalId' => GlobalIdFieldDefinition::class,
        'Relay::Node' => NodeFieldDefinition::class,
        'Relay::PluralIdentifyingRoot' => PluralIdentifyingRootFieldDefinition::class,
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
        $interface = MappingInterface::class;

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
     * @param string $name
     * @param string $type
     *
     * @return MappingInterface
     *
     * @throws InvalidConfigurationException if builder class not define
     */
    protected function getBuilder($name, $type)
    {
        static $builders = [];
        if (isset($builders[$type][$name])) {
            return $builders[$type][$name];
        }

        $builderClassMap = self::${$type.'BuilderClassMap'};

        if (isset($builderClassMap[$name])) {
            return $builders[$type][$name] = new $builderClassMap[$name]();
        }
        // deprecated relay builder name ?
        $newName = 'Relay::'.rtrim($name, 'Args');
        if (isset($builderClassMap[$newName])) {
            @trigger_error(
                sprintf('The "%s" %s builder is deprecated as of 0.7 and will be removed in 1.0. Use "%s" instead.', $name, $type, $newName),
                E_USER_DEPRECATED
            );

            return $builders[$type][$newName] = new $builderClassMap[$newName]();
        }

        throw new InvalidConfigurationException(sprintf('%s builder "%s" not found.', ucfirst($type), $name));
    }

    protected function outputFieldsSelection($name)
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

                    $builderConfig = [];
                    if (isset($field['argsBuilder']['config']) && is_array($field['argsBuilder']['config'])) {
                        $builderConfig = $field['argsBuilder']['config'];
                    }

                    if ($argsBuilderName) {
                        $args = $this->getBuilder($argsBuilderName, static::BUILDER_ARGS_TYPE)->toMappingDefinition($builderConfig);
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
                        @trigger_error(
                            'The builder short syntax (Field: Builder => Field: {builder: Builder}) is deprecated as of 0.7 and will be removed in 0.9. '.
                            'It will be replaced by the field type short syntax (Field: Type => Field: {type: Type})',
                            E_USER_DEPRECATED
                        );
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
                        $buildField = $this->getBuilder($fieldBuilderName, static::BUILDER_FIELD_TYPE)->toMappingDefinition($builderConfig);
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
                        // Allow arg type short syntax (Arg: Type => Arg: {type: Type})
                        ->beforeNormalization()
                            ->ifTrue(function ($options) {
                                return is_string($options);
                            })
                            ->then(function ($options) {
                                return ['type' => $options];
                            })
                        ->end()
                        ->children()
                            ->append($this->typeSelection(true))
                            ->append($this->descriptionSection())
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
                ->variableNode('public')
                    ->info('Visibility control to field (expression language can be use here)')
                ->end()
                ->variableNode('complexity')
                    ->info('Custom complexity calculator.')
                ->end()
            ->end();

        return $node;
    }
}
