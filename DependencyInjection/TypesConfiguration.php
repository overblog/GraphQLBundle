<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @todo fix xml
 */
class TypesConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('overblog_graphql_types');

        $rootNode
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
                            'relay-node',
                        ])
                        ->isRequired()
                    ->end()
                    ->append($this->addConfigSelection())
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function addConfigSelection()
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
            ->variableNode('fieldsDefaultAccess')
                ->info('Default access control to fields (expression language can be use here)')
            ->end()
            ->append($this->addFieldsSelection('fields'))
            ->variableNode('resolveType')->end()
            ->arrayNode('values')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->beforeNormalization()
                        ->ifTrue(function ($v) { return !is_null($v) && !is_array($v); })
                        ->then(function ($v) { return ['value' => $v]; })
                    ->end()
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
            ->variableNode('resolveCursor')->end()
            ->variableNode('resolveNode')->end()
        ->end()
        ->validate()
            ->always(function ($v) {
                // remove all empty value
                $array_filter_recursive = function ($input) use (&$array_filter_recursive) {
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

                    $cleanInput = [];

                    foreach ($input as $key => $val) {
                        if ('defaultValue' === $key || !is_null($val)) {
                            $cleanInput[$key] = $val;
                        }
                    }

                    return $cleanInput;
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
                    ->then(function ($v) { return ['builder' => $v]; })
                ->end()
                ->children()
                    ->append($this->addTypeSelection())
                    ->arrayNode('argsBuilder')
                        ->info('Use to build dynamic args. Can be combine with args.')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function ($v) { return ['builder' => $v]; })
                        ->end()
                        ->children()
                            ->scalarNode('builder')
                                ->info('Service alias tagged with "overblog_graphql.arg"')
                                ->isRequired()
                                ->end()
                            ->variableNode('config')->end()
                        ->end()
                    ->end()
                    ->arrayNode('args')
                        ->info('Array of possible type arguments. Each entry is expected to be an array with following keys: name (string), type')
                        ->useAttributeAsKey('name')
                        ->prototype('array')
                            ->children()
                                ->append($this->addTypeSelection(true))
                                ->scalarNode('description')->end()
                                ->variableNode('defaultValue')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->variableNode('resolve')
                        ->info('Value resolver (expression language can be use here)')
                    ->end()
                    ->scalarNode('description')
                        ->info('Field description for clients')
                    ->end()
                    ->append($this->addDeprecationReasonSelection())
                    ->variableNode('access')
                        ->info('Access control to field (expression language can be use here)')
                    ->end();

        if ($enabledBuilder) {
            $prototype
                    ->scalarNode('builder')
                        ->info('Service alias tagged with "overblog_graphql.field"')
                    ->end()
                    ->variableNode('builderConfig')->end();
        }

        $prototype
                    ->scalarNode('complexity')
                        ->info('Custom complexity calculator.')
                    ->end()
                ->end()
            ->end();

        $node->validate()
            ->ifTrue(function ($fields) use ($enabledBuilder) {
                foreach ($fields as $v) {
                    if (empty($v['type']) && $enabledBuilder && empty($v['builder'])) {
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
