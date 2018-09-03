<?php

namespace Overblog\GraphQLBundle\Config\Processor;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\Relay\Connection\BackwardConnectionArgsDefinition;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionArgsDefinition;
use Overblog\GraphQLBundle\Relay\Connection\ForwardConnectionArgsDefinition;
use Overblog\GraphQLBundle\Relay\Mutation\MutationFieldDefinition;
use Overblog\GraphQLBundle\Relay\Node\GlobalIdFieldDefinition;
use Overblog\GraphQLBundle\Relay\Node\NodeFieldDefinition;
use Overblog\GraphQLBundle\Relay\Node\PluralIdentifyingRootFieldDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class BuilderProcessor implements ProcessorInterface
{
    const BUILDER_FIELD_TYPE = 'field';
    const BUILDER_ARGS_TYPE = 'args';

    const BUILDER_TYPES = [
        self::BUILDER_FIELD_TYPE,
        self::BUILDER_ARGS_TYPE,
    ];

    /** @var MappingInterface[] */
    private static $builderClassMap = [
        self::BUILDER_ARGS_TYPE => [
            'Relay::ForwardConnection' => ForwardConnectionArgsDefinition::class,
            'Relay::BackwardConnection' => BackwardConnectionArgsDefinition::class,
            'Relay::Connection' => ConnectionArgsDefinition::class,
        ],
        self::BUILDER_FIELD_TYPE => [
            'Relay::Mutation' => MutationFieldDefinition::class,
            'Relay::GlobalId' => GlobalIdFieldDefinition::class,
            'Relay::Node' => NodeFieldDefinition::class,
            'Relay::PluralIdentifyingRoot' => PluralIdentifyingRootFieldDefinition::class,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public static function process(array $configs)
    {
        foreach ($configs as &$config) {
            if (isset($config['config']['fields']) && \is_array($config['config']['fields'])) {
                $config['config']['fields'] = self::processFieldBuilders($config['config']['fields']);
            }
        }

        return $configs;
    }

    public static function addBuilderClass($name, $type, $builderClass)
    {
        self::checkBuilderClass($builderClass, $type);
        self::$builderClassMap[$type][$name] = $builderClass;
    }

    /**
     * @param string $builderClass
     * @param string $type
     */
    private static function checkBuilderClass($builderClass, $type)
    {
        $interface = MappingInterface::class;

        if (!\is_string($builderClass)) {
            throw new \InvalidArgumentException(
                \sprintf('%s builder class should be string, but %s given.', \ucfirst($type), \gettype($builderClass))
            );
        }

        if (!\class_exists($builderClass)) {
            throw new \InvalidArgumentException(
                \sprintf('%s builder class "%s" not found.', \ucfirst($type), $builderClass)
            );
        }

        if (!\is_subclass_of($builderClass, $interface)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    '%s builder class should implement "%s", but "%s" given.',
                    \ucfirst($type),
                    $interface,
                    $builderClass
                )
            );
        }
    }

    private static function processFieldBuilders(array $fields)
    {
        foreach ($fields as &$field) {
            $fieldBuilderName = null;

            if (isset($field['builder']) && \is_string($field['builder'])) {
                $fieldBuilderName = $field['builder'];
                unset($field['builder']);
            } elseif (\is_string($field)) {
                @\trigger_error(
                    'The builder short syntax (Field: Builder => Field: {builder: Builder}) is deprecated as of 0.7 and will be removed in 0.12. '.
                    'It will be replaced by the field type short syntax (Field: Type => Field: {type: Type})',
                    \E_USER_DEPRECATED
                );
                $fieldBuilderName = $field;
            }

            $builderConfig = [];
            if (isset($field['builderConfig'])) {
                if (\is_array($field['builderConfig'])) {
                    $builderConfig = $field['builderConfig'];
                }
                unset($field['builderConfig']);
            }

            if ($fieldBuilderName) {
                $buildField = self::getBuilder($fieldBuilderName, self::BUILDER_FIELD_TYPE)->toMappingDefinition($builderConfig);
                $field = \is_array($field) ? \array_merge($buildField, $field) : $buildField;
            }
            if (isset($field['argsBuilder'])) {
                $field = self::processFieldArgumentsBuilders($field);
            }
        }

        return $fields;
    }

    /**
     * @param string $name
     * @param string $type
     *
     * @return MappingInterface
     *
     * @throws InvalidConfigurationException if builder class not define
     */
    private static function getBuilder($name, $type)
    {
        static $builders = [];
        if (isset($builders[$type][$name])) {
            return $builders[$type][$name];
        }

        $builderClassMap = self::$builderClassMap[$type];

        if (isset($builderClassMap[$name])) {
            return $builders[$type][$name] = new $builderClassMap[$name]();
        }
        // deprecated relay builder name ?
        $newName = 'Relay::'.\rtrim($name, 'Args');
        if (isset($builderClassMap[$newName])) {
            @\trigger_error(
                \sprintf('The "%s" %s builder is deprecated as of 0.7 and will be removed in 0.12. Use "%s" instead.', $name, $type, $newName),
                \E_USER_DEPRECATED
            );

            return $builders[$type][$newName] = new $builderClassMap[$newName]();
        }

        throw new InvalidConfigurationException(\sprintf('%s builder "%s" not found.', \ucfirst($type), $name));
    }

    private static function processFieldArgumentsBuilders(array $field)
    {
        $argsBuilderName = null;

        if (\is_string($field['argsBuilder'])) {
            $argsBuilderName = $field['argsBuilder'];
        } elseif (isset($field['argsBuilder']['builder']) && \is_string($field['argsBuilder']['builder'])) {
            $argsBuilderName = $field['argsBuilder']['builder'];
        }

        $builderConfig = [];
        if (isset($field['argsBuilder']['config']) && \is_array($field['argsBuilder']['config'])) {
            $builderConfig = $field['argsBuilder']['config'];
        }

        if ($argsBuilderName) {
            $args = self::getBuilder($argsBuilderName, self::BUILDER_ARGS_TYPE)->toMappingDefinition($builderConfig);
            $field['args'] = isset($field['args']) && \is_array($field['args']) ? \array_merge($args, $field['args']) : $args;
        }

        unset($field['argsBuilder']);

        return $field;
    }
}
