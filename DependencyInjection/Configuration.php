<?php

namespace Overblog\GraphBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('overblog_graph');

        $baseTypes = ['object', 'enum', 'interface', 'union', 'inputObject', 'connection'];

        $rootNode
            ->children()
                ->variableNode('references')
                    ->info('This path designed to ease yaml reference implementation')
                ->end()
                ->arrayNode('definitions')
                    ->children()
                        ->arrayNode('schema')
                            ->children()
                                ->scalarNode('query')->end()
                                ->scalarNode('mutation')->end()
                            ->end()
                        ->end()
                        ->arrayNode('types')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->enumNode('type')->values($baseTypes)
                                        ->isRequired()
                                    ->end()
                                    ->append($this->addConfigNode())
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->always()
                ->then(function($v) {
                    $definitions = $v['definitions'];
                    $types = array_keys($definitions['types']);
                    $typesString = implode('", "', $types);

                    foreach(['query', 'mutation'] as $key) {
                        if (!empty($definitions['schema'][$key]) && !in_array($definitions['schema'][$key], $types)) {
                            $exception = new InvalidConfigurationException(
                                sprintf(
                                    'Invalid value for path definitions.schema.%s values "%s" (must be one of type: "%s")',
                                    $key,
                                    $definitions['schema']['query'],
                                    $typesString
                                )
                            );

                            throw $exception;
                        }
                    }

                    return $v;
                })
            ->end()
    ;

        return $treeBuilder;
    }

    /**
     * @todo add deprecationReasons
     *
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    private function addConfigNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('config');

        $node->children()
            ->scalarNode('description')->end()
            ->scalarNode('isTypeOf')->end()
            ->arrayNode('types')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('fields')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('type')->end()
                        ->arrayNode('args')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('type')->isRequired()->end()
                                    ->scalarNode('defaultValue')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('resolve')->end()
                        ->scalarNode('description')->end()
                        ->scalarNode('deprecationReason')->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('resolveType')->end()
            ->arrayNode('values')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->isRequired()
                    ->children()
                        ->scalarNode('value')->end()
                        ->scalarNode('description')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('interfaces')
                ->prototype('scalar')->end()
            ->end()
            ->scalarNode('nodeType')->end()
            ->arrayNode('edgeFields')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('type')->end()
                        ->scalarNode('resolve')->end()
                        ->scalarNode('description')->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('connectionFields')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('type')->end()
                        ->scalarNode('resolve')->end()
                        ->scalarNode('description')->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('resolveCursor')->end()
            ->scalarNode('resolveNode')->end()
        ->end();

        return $node;
    }
}
