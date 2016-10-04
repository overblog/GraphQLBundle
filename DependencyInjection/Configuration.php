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

use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @todo fix xml
 */
class Configuration implements ConfigurationInterface
{
    private $debug;

    /**
     * Constructor.
     *
     * @param bool $debug Whether to use the debug mode
     */
    public function __construct($debug)
    {
        $this->debug = (bool) $debug;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('overblog_graphql');

        $rootNode
            ->children()
                ->arrayNode('definitions')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('internal_error_message')->defaultNull()->end()
                        ->booleanNode('show_debug_info')->defaultValue(false)->end()
                        ->booleanNode('config_validation')->defaultValue($this->debug)->end()
                        ->arrayNode('schema')
                            ->beforeNormalization()
                                ->ifTrue(function ($v) {
                                    $needNormalization = isset($v['query']) && is_string($v['query']) ||
                                        isset($v['mutation']) && is_string($v['mutation']) ||
                                        isset($v['subscription']) && is_string($v['subscription']);

                                    return $needNormalization;
                                })
                                ->then(function ($v) {
                                    return ['default' => $v];
                                })
                            ->end()
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('query')->defaultNull()->end()
                                    ->scalarNode('mutation')->defaultNull()->end()
                                    ->scalarNode('subscription')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('mappings')
                            ->children()
                                ->arrayNode('types')
                                    ->prototype('array')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->enumNode('type')->isRequired()->values(['yml', 'xml'])->end()
                                            ->scalarNode('dir')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('exceptions')
                            ->children()
                                ->arrayNode('warnings')
                                    ->treatNullLike([])
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('errors')
                                    ->treatNullLike([])
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('types')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('warnings')
                                            ->defaultValue('Overblog\\GraphQLBundle\\Error\\UserWarning')
                                        ->end()
                                        ->scalarNode('errors')
                                            ->defaultValue('Overblog\\GraphQLBundle\\Error\\UserError')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('builders')
                            ->children()
                                ->arrayNode('field')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('alias')->isRequired()->end()
                                            ->scalarNode('class')->isRequired()->end()
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('args')
                                    ->prototype('array')
                                        ->children()
                                            ->scalarNode('alias')->isRequired()->end()
                                            ->scalarNode('class')->isRequired()->end()
                                        ->end()
                                    ->end()
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
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->append($this->addSecurityQuerySection('query_max_depth', QueryDepth::DISABLED))
                        ->append($this->addSecurityQuerySection('query_max_complexity', QueryComplexity::DISABLED))
                    ->end()
                ->end()
                ->arrayNode('versions')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('graphiql')->defaultValue('0.7.8')->end()
                        ->scalarNode('react')->defaultValue('15.3.2')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function addSecurityQuerySection($name, $disabledValue)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name, 'integer');

        $node
            ->info('Disabled if equal to false.')
            ->beforeNormalization()
                ->ifTrue(function ($v) {
                    return false === $v;
                })
                ->then(function () use ($disabledValue) {
                    return $disabledValue;
                })
            ->end()
            ->defaultFalse()
            ->validate()
                ->ifTrue(function ($v) {
                    return $v < 0;
                })
                ->thenInvalid('"overblog_graphql.security.'.$name.'" must be greater or equal to 0.')
            ->end()
        ;

        return $node;
    }
}
