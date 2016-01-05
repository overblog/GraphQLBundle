<?php

namespace Overblog\GraphBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('overblog_graph');

        $rootNode
            ->children()
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
                                    ->enumNode('type')->values(['object', 'enum', 'interface', 'union', 'inputObject'])->isRequired()->end()
                                    ->append($this->addConfigNode())
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
    ;

        return $treeBuilder;
    }

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
        ->end();

        return $node;
    }
}
