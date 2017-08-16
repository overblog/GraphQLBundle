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
use Overblog\GraphQLBundle\Error\ErrorHandler;
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
                            ->addDefaultsIfNotSet()
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
                                            ->defaultValue(ErrorHandler::DEFAULT_USER_WARNING_CLASS)
                                        ->end()
                                        ->scalarNode('errors')
                                            ->defaultValue(ErrorHandler::DEFAULT_USER_ERROR_CLASS)
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()

                        ->arrayNode('builders')
                            ->children()
                                ->append($this->addBuilderSection('field'))
                                ->append($this->addBuilderSection('args'))
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
                        ->scalarNode('executor')
                            ->defaultValue('overblog_graphql.executor.default')
                        ->end()
                        ->scalarNode('promise_adapter')
                            ->defaultValue('overblog_graphql.promise_adapter.default')
                        ->end()
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
                        ->scalarNode('graphiql')->defaultValue('0.9')->end()
                        ->scalarNode('react')->defaultValue('15.4')->end()
                        ->scalarNode('fetch')->defaultValue('2.0')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function addBuilderSection($name)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);
        $node->beforeNormalization()
            ->ifTrue(function ($v) {
                return is_array($v) && !empty($v);
            })
            ->then(function ($v) {
                foreach ($v as $key => &$config) {
                    if (is_string($config)) {
                        $config = [
                            'alias' => $key,
                            'class' => $config,
                        ];
                    }
                }

                return $v;
            })
        ->end();

        $node->prototype('array')
            ->children()
                ->scalarNode('alias')->isRequired()->end()
                ->scalarNode('class')->isRequired()->end()
            ->end()
        ->end()
        ;

        return $node;
    }

    private function addSecurityQuerySection($name, $disabledValue)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name, 'integer');
        $node->beforeNormalization()
                ->ifTrue(function ($v) {
                    return is_string($v) && is_numeric($v);
                })
                ->then(function ($v) {
                    return intval($v);
                })
            ->end();

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
