<?php

namespace Overblog\GraphQLBundle\DependencyInjection;

use Overblog\GraphQLBundle\Config;
use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionDefinition;
use Overblog\GraphQLBundle\Relay\Mutation\InputDefinition;
use Overblog\GraphQLBundle\Relay\Mutation\PayloadDefinition;
use Overblog\GraphQLBundle\Relay\Node\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class TypesConfiguration implements ConfigurationInterface
{
    private static $types = [
        'object',
        'enum',
        'interface',
        'union',
        'input-object',
        'custom-scalar',
    ];

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('overblog_graphql_types');

        $configTypeKeys = array_map(
            function ($type) {
                return $this->normalizedConfigTypeKey($type);
            },
            self::$types
        );

        $this->addBeforeNormalization($rootNode);

        $rootNode
            ->useAttributeAsKey('name')
            ->prototype('array')
                // config is the unique config entry allowed
                ->beforeNormalization()
                    ->ifTrue(function ($v) use ($configTypeKeys) {
                        if (!empty($v) && is_array($v)) {
                            $keys = array_keys($v);
                            foreach ($configTypeKeys as $configTypeKey) {
                                if (in_array($configTypeKey, $keys)) {
                                    return true;
                                }
                            }
                        }

                        return  false;
                    })
                        ->thenInvalid(
                            sprintf(
                                'Don\'t use internal config keys %s, replace it by "config" instead.',
                                implode(', ', $configTypeKeys)
                            )
                        )
                ->end()
                // config is renamed _{TYPE}_config
                ->beforeNormalization()
                    ->ifTrue(function ($v) {
                        return isset($v['type']) && is_string($v['type']);
                    })
                    ->then(function ($v) {
                        $key = $this->normalizedConfigTypeKey($v['type']);

                        if (empty($v[$key])) {
                            $v[$key] = isset($v['config']) ? $v['config'] : [];
                        }
                        unset($v['config']);

                        return $v;
                    })
                ->end()
                ->cannotBeOverwritten()
                ->children()
                    ->enumNode('type')->values(self::$types)->isRequired()->end()
                    ->append(Config\ObjectTypeDefinition::create()->getDefinition())
                    ->append(Config\EnumTypeDefinition::create()->getDefinition())
                    ->append(Config\InterfaceTypeDefinition::create()->getDefinition())
                    ->append(Config\UnionTypeDefinition::create()->getDefinition())
                    ->append(Config\InputObjectTypeDefinition::create()->getDefinition())
                    ->append(Config\CustomScalarTypeDefinition::create()->getDefinition())
                    ->variableNode('config')->end()
                ->end()
                // _{TYPE}_config is renamed config
                ->validate()
                    ->ifTrue(function ($v) {
                        return isset($v[$this->normalizedConfigTypeKey($v['type'])]);
                    })
                    ->then(function ($v) {
                        $key = $this->normalizedConfigTypeKey($v['type']);
                        $v['config'] = $v[$key];
                        unset($v[$key]);

                        return $v;
                    })
                ->end()

            ->end();

        return $treeBuilder;
    }

    private function addBeforeNormalization(ArrayNodeDefinition $node)
    {
        $typeKeyExists = function ($types) {
            return !empty($types) && is_array($types);
        };

        $node
            // set type config.name
            ->beforeNormalization()
                ->ifTrue($typeKeyExists)
                ->then(function ($types) {
                    foreach ($types as $name => &$type) {
                        $type['config'] = isset($type['config']) && is_array($type['config']) ? $type['config'] : [];
                        $type['config']['name'] = $name;
                    }

                    return $types;
                })
            ->end()
            // normalized relay-connection
            ->beforeNormalization()
                ->ifTrue($typeKeyExists)
                ->then($this->relayNormalizer('relay-connection', ConnectionDefinition::class))
            ->end()
            // normalized relay-node
            ->beforeNormalization()
                ->ifTrue($typeKeyExists)
                ->then($this->relayNormalizer('relay-node', NodeDefinition::class))
            ->end()
            // normalized relay-mutation-input
            ->beforeNormalization()
                ->ifTrue($typeKeyExists)
                ->then($this->relayNormalizer('relay-mutation-input', InputDefinition::class))
            ->end()
            // normalized relay-mutation-payload
            ->beforeNormalization()
                ->ifTrue(function ($types) {
                    return !empty($types) && is_array($types);
                })
                ->then($this->relayNormalizer('relay-mutation-payload', PayloadDefinition::class))
            ->end();
    }

    private function relayNormalizer($typeToTreat, $definitionBuilderClass)
    {
        return function ($types) use ($typeToTreat, $definitionBuilderClass) {
            foreach ($types as $name => $type) {
                if (isset($type['type']) && is_string($type['type']) && $typeToTreat === $type['type']) {
                    $config = isset($type['config']) && is_array($type['config']) ? $type['config'] : [];
                    $config['name'] = $name;

                    /** @var MappingInterface $builder */
                    $builder = new $definitionBuilderClass();

                    $connectionDefinition = $builder->toMappingDefinition($config);

                    $types = array_replace($types, $connectionDefinition);
                }
            }

            return $types;
        };
    }

    private function normalizedConfigTypeKey($type)
    {
        return '_'.str_replace('-', '_', $type).'_config';
    }
}
