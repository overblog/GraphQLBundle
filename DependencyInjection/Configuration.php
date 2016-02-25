<?php

namespace Overblog\GraphQLBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('overblog_graphql');

        $rootNode
            ->children()
//                ->variableNode('references')
//                    ->info('This path designed to ease yaml reference implementation')
//                ->end()
                ->arrayNode('definitions')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('internal_error_message')->defaultNull()->end()
                        ->booleanNode('config_validation')->defaultFalse()->end()
                        ->arrayNode('schema')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('query')->defaultNull()->end()
                                ->scalarNode('mutation')->defaultNull()->end()
                                ->scalarNode('subscription')->defaultNull()->end()
                            ->end()
                        ->end()
                        ->arrayNode('types')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->enumNode('type')->values([
                                            'object',
                                            'enum',
                                            'interface',
                                            'union',
                                            'input-object',
                                            'relay-mutation-input',
                                            'relay-mutation-payload',
                                            'relay-connection',
                                            'relay-node'
                                        ])
                                        ->isRequired()
                                    ->end()
                                    ->append($this->addConfigNode())
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('templates')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('graphiql')
                            ->defaultValue('OverblogGraphQLBundle:GraphiQL:index.html.twig')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('services')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('expression_language')
                            ->defaultValue('overblog_graphql.expression_language.default')
                        ->end()
                        ->scalarNode('cache_expression_language_parser')
                            ->defaultValue('overblog_graphql.cache_expression_language_parser.default')
                        ->end()
                    ->end()
                ->end()
            ->end();

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
                ->prototype('scalar')
                    ->info('One of internal or custom types.')
                ->end()
            ->end()
            ->append($this->addFieldsSelection('fields'))
            ->scalarNode('resolveType')->end()
            ->arrayNode('values')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->isRequired()
                    ->children()
                        ->scalarNode('value')->end()
                        ->scalarNode('description')->end()
                        ->append($this->addDeprecationReasonSelection())
                    ->end()
                ->end()
            ->end()
            ->arrayNode('interfaces')
                ->prototype('scalar')
                    ->info('One of internal or custom interface types.')
                ->end()
            ->end()
            ->scalarNode('nodeType')->end()
            ->append($this->addFieldsSelection('edgeFields'))
            ->append($this->addFieldsSelection('connectionFields'))
            ->scalarNode('resolveCursor')->end()
            ->scalarNode('resolveNode')->end()
        ->end()
        ->validate()
            ->always(function($v) {
                // remove all empty value
                $array_filter_recursive = function ($input) use (&$array_filter_recursive){
                    foreach ($input as $key => &$value) {
                        if ('defaultValue' === $key) {
                            continue;
                        }

                        if (is_array($value)) {
                            if (empty($value)) {
                                unset($input[$key]);
                            } else {
                                $value = $array_filter_recursive($value);
                            }
                        }
                    }

                    return array_filter($input, function ($key, $val) { return 'defaultValue' === $key || !is_null($val); }, ARRAY_FILTER_USE_BOTH);
                };

                return $array_filter_recursive($v);
            })
        ->end();

        return $node;
    }

    private function addFieldsSelection($name, $enabledBuilder = true)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);

        $prototype = $node->useAttributeAsKey('name')
            ->prototype('array')
                ->beforeNormalization()
                    ->ifString()
                    ->then(function ($v) { return array('builder' => $v); })
                ->end()
                ->children()
                    ->append($this->addTypeSelection())
                    ->arrayNode('argsBuilder')
                        ->info('Use to build dynamic args. Can be combine with args.')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function ($v) { return array('name' => $v); })
                        ->end()
                        ->children()
                            ->scalarNode('name')
                                ->info('Service alias tagged with "overblog_graphql.arg"')
                                ->isRequired()
                                ->end()
                            ->arrayNode('config')->end()
                        ->end()
                    ->end()
                    ->arrayNode('args')
                        ->info('Array of possible type arguments. Each entry is expected to be an array with following keys: name (string), type')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->append($this->addTypeSelection(true))
                                ->variableNode('defaultValue')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('resolve')
                        ->info('Value resolver (expression language can be use here)')
                    ->end()
                    ->scalarNode('description')
                        ->info('Field description for clients')
                    ->end()
                    ->append($this->addDeprecationReasonSelection())
                    ->scalarNode('access')
                        ->info('Access control to field (expression language can be use here)')
                    ->end();

        if ($enabledBuilder) {
            $prototype
                    ->scalarNode('builder')
                        ->info('Service alias tagged with "overblog_graphql.field"')
                    ->end()
                    ->arrayNode('builderConfig')
                        ->children()
                            ->scalarNode('nodeInterfaceType')->end()
                            ->scalarNode('mutateAndGetPayload')->end()
                            ->scalarNode('typeName')->end()
                            ->scalarNode('idFetcher')->end()
                            ->scalarNode('typeResolver')->end()
                            ->scalarNode('argName')->end()
                            ->scalarNode('inputType')->end()
                            ->scalarNode('outputType')->end()
                            ->scalarNode('payloadType')->end()
                            ->scalarNode('resolveSingleInput')->end()
                            ->scalarNode('description')->end()
                        ->end()
                    ->end();
        }

        $prototype
                ->end()
            ->end();

        $node->validate()
            ->ifTrue(function($fields) use($enabledBuilder) {
                foreach($fields as $v) {
                    if(empty($v['type']) && $enabledBuilder && empty($v['builder'])) {
                        return true;
                    }
                }

                return false;
            })
            ->thenInvalid('Type or builder is required')
        ->end();

        return $node;
    }

    private function addDeprecationReasonSelection()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('deprecationReason', 'scalar');

        $node->info('Text describing why this field is deprecated. When not empty - field will not be returned by introspection queries (unless forced)');

        return $node;
    }

    private function addTypeSelection($isRequired = false)
    {
        $builder = new TreeBuilder();
        $node = $builder->root('type', 'scalar');

        $node->info('One of internal or custom types.');

        if ($isRequired) {
            $node->isRequired();
        }

        return $node;
    }
}
