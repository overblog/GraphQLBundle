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
        ->arrayNode('schema')
          ->addDefaultsIfNotSet()
          ->children()
            ->arrayNode('queries')
              ->useAttributeAsKey('name')
              ->prototype('scalar')->end()
            ->end()
            ->arrayNode('mutations')
              ->useAttributeAsKey('name')
              ->prototype('scalar')->end()
            ->end()
          ->end()
        ->end()
        ->arrayNode('types')
          ->useAttributeAsKey('name')
          ->prototype('scalar')->end()
        ->end()
      ->end()
    ;

        return $treeBuilder;
    }
}
