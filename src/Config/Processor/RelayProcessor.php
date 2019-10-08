<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Processor;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionDefinition;
use Overblog\GraphQLBundle\Relay\Mutation\InputDefinition;
use Overblog\GraphQLBundle\Relay\Mutation\PayloadDefinition;
use Overblog\GraphQLBundle\Relay\Node\NodeDefinition;

final class RelayProcessor implements ProcessorInterface
{
    public const RELAY_DEFINITION_MAPPING = [
        'relay-connection' => ConnectionDefinition::class,
        'relay-node' => NodeDefinition::class,
        'relay-mutation-input' => InputDefinition::class,
        'relay-mutation-payload' => PayloadDefinition::class,
    ];

    /**
     * {@inheritdoc}
     */
    public static function process(array $configs): array
    {
        foreach (static::RELAY_DEFINITION_MAPPING as $typeName => $definitionBuilderClass) {
            $configs = self::processRelayConfigs($typeName, $definitionBuilderClass, $configs);
        }

        return $configs;
    }

    private static function processRelayConfigs($typeName, $definitionBuilderClass, array $configs)
    {
        foreach ($configs as $name => $config) {
            if (isset($config['type']) && \is_string($config['type']) && $typeName === $config['type']) {
                $configInherits = isset($config['inherits']) && \is_array($config['inherits']) ? $config['inherits'] : [];

                $config = isset($config['config']) && \is_array($config['config']) ? $config['config'] : [];

                if (empty($config['class_name'])) {
                    $config['class_name'] = \sprintf('%sType', $name);
                }
                if (empty($config['name'])) {
                    $config['name'] = $name;
                }

                /** @var MappingInterface $builder */
                $builder = new $definitionBuilderClass();

                $connectionDefinition = $builder->toMappingDefinition($config);

                if (!empty($configInherits)) {
                    $connectionDefinition[$name]['inherits'] = $configInherits;
                }

                $configs = \array_replace($configs, $connectionDefinition);
            }
        }

        return $configs;
    }
}
