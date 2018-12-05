<?php

namespace Overblog\GraphQLBundle\DependencyInjection;

use Overblog\GraphQLBundle\Config;
use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
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
        $treeBuilder = new TreeBuilder('overblog_graphql_types');
        $rootNode = Configuration::getRootNodeWithoutDeprecation($treeBuilder, 'overblog_graphql_types');

        $configTypeKeys = \array_map(
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
                        if (!empty($v) && \is_array($v)) {
                            $keys = \array_keys($v);
                            foreach ($configTypeKeys as $configTypeKey) {
                                if (\in_array($configTypeKey, $keys)) {
                                    return true;
                                }
                            }
                        }

                        return  false;
                    })
                        ->thenInvalid(
                            \sprintf(
                                'Don\'t use internal config keys %s, replace it by "config" instead.',
                                \implode(', ', $configTypeKeys)
                            )
                        )
                ->end()
                // config is renamed _{TYPE}_config
                ->beforeNormalization()
                    ->ifTrue(function ($v) {
                        return isset($v['type']) && \is_string($v['type']);
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
                    ->scalarNode('class_name')
                        ->isRequired()
                        ->validate()
                            ->ifTrue(function ($name) {
                                return !\preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name);
                            })
                            ->thenInvalid('A valid class name starts with a letter or underscore, followed by any number of letters, numbers, or underscores.')
                        ->end()
                    ->end()
                    ->enumNode('type')->values(self::$types)->isRequired()->end()
                    ->arrayNode(InheritanceProcessor::INHERITS_KEY)
                        ->prototype('scalar')->info('Types to inherit of.')->end()
                    ->end()
                    ->booleanNode('decorator')->info('Decorator will not be generated.')->defaultFalse()->end()
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
        $node
            // process beforeNormalization (should be execute after relay normalization)
            ->beforeNormalization()
                ->ifTrue(function ($types) {
                    return \is_array($types);
                })
                ->then(function ($types) {
                    return Config\Processor::process($types, Config\Processor::BEFORE_NORMALIZATION);
                })
            ->end()
            ;
    }

    private function normalizedConfigTypeKey($type)
    {
        return '_'.\str_replace('-', '_', $type).'_config';
    }
}
