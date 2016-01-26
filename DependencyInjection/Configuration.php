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

        $rootNode
            ->children()
                ->variableNode('references')
                    ->info('This path designed to ease yaml reference implementation')
                ->end()
                ->arrayNode('definitions')
                    ->children()
                        ->booleanNode('config_validation')->isRequired()->defaultValue(false)->end()
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
                                    ->enumNode('type')->values(['object', 'enum', 'interface', 'union', 'inputObject', 'connection'])
                                        ->isRequired()
                                    ->end()
                                    ->append($this->addConfigNode())
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

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
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) { return array('builder' => $v); })
                    ->end()
                    ->children()
                        ->scalarNode('builder')->end()
                        ->arrayNode('builderConfig')
                            ->children()
                                ->scalarNode('nodeInterfaceType')->end()
                                ->arrayNode('inputFields')
                                    ->useAttributeAsKey('name')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('type')->end()
                                            ->scalarNode('resolve')->end()
                                            ->scalarNode('description')->end()
                                        ->end()
                                    ->end()
                                ->end()
                                 ->arrayNode('inputFields')
                                    ->useAttributeAsKey('name')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('type')->end()
                                            ->scalarNode('resolve')->end()
                                            ->scalarNode('description')->end()
                                        ->end()
                                    ->end()
                                ->end()
                                 ->arrayNode('outputFields')
                                    ->useAttributeAsKey('name')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('type')->end()
                                            ->scalarNode('resolve')->end()
                                            ->scalarNode('description')->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->scalarNode('mutateAndGetPayload')->end()
                                ->scalarNode('typeName')->end()
                                ->scalarNode('idFetcher')->end()
                                ->scalarNode('typeResolver')->end()
                            ->end()
                        ->end()
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
