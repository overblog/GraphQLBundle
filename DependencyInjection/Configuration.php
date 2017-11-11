<?php

namespace Overblog\GraphQLBundle\DependencyInjection;

use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Resolver\Resolver;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /** bool */
    private $debug;

    /** null|string */
    private $cacheDir;

    /**
     * Constructor.
     *
     * @param bool        $debug    Whether to use the debug mode
     * @param null|string $cacheDir
     */
    public function __construct($debug, $cacheDir = null)
    {
        $this->debug = (bool) $debug;
        $this->cacheDir = $cacheDir;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('overblog_graphql');

        $rootNode
            ->children()
                ->enumNode('batching_method')
                    ->values(['relay', 'apollo'])
                    ->defaultValue('relay')
                ->end()
                ->arrayNode('definitions')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('internal_error_message')->defaultNull()->end()
                        ->variableNode('default_resolver')->defaultValue([Resolver::class, 'defaultResolveFn'])->end()
                        ->scalarNode('class_namespace')->defaultValue('Overblog\\GraphQLBundle\\__DEFINITIONS__')->end()
                        ->scalarNode('cache_dir')->defaultValue($this->cacheDir.'/overblog/graphql-bundle/__definitions__')->end()
                        ->booleanNode('use_classloader_listener')->defaultTrue()->end()
                        ->booleanNode('auto_compile')->defaultTrue()->end()
                        ->booleanNode('show_debug_info')->defaultFalse()->end()
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
                        ->arrayNode('auto_mapping')
                            ->treatFalseLike(['enabled' => false])
                            ->treatTrueLike(['enabled' => true])
                            ->treatNullLike(['enabled' => true])
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->arrayNode('directories')
                                    ->info('List of directories containing GraphQL classes.')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('mappings')
                            ->children()
                                ->arrayNode('auto_discover')
                                    ->treatFalseLike(['bundles' => false, 'root_dir' => false])
                                    ->treatTrueLike(['bundles' => true, 'root_dir' => true])
                                    ->treatNullLike(['bundles' => true, 'root_dir' => true])
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->booleanNode('bundles')->defaultTrue()->end()
                                        ->booleanNode('root_dir')->defaultTrue()->end()
                                    ->end()
                                ->end()
                                ->arrayNode('types')
                                    ->prototype('array')
                                        ->addDefaultsIfNotSet()
                                        ->beforeNormalization()
                                            ->ifTrue(function ($v) {
                                                return isset($v['type']) && 'yml' === $v['type'];
                                            })
                                            ->then(function ($v) {
                                                $v['type'] = 'yaml';

                                                return $v;
                                            })
                                        ->end()
                                        ->children()
                                            ->enumNode('type')->values(['yaml', 'xml'])->defaultNull()->end()
                                            ->scalarNode('dir')->defaultNull()->end()
                                            ->scalarNode('suffix')->defaultValue(OverblogGraphQLTypesExtension::DEFAULT_TYPES_SUFFIX)->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->booleanNode('map_exceptions_to_parent')->defaultFalse()->end()
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
                            ->defaultValue('@OverblogGraphQL/GraphiQL/index.html.twig')
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
                        ->booleanNode('handle_cors')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('versions')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('graphiql')->defaultValue('0.11')->end()
                        ->scalarNode('react')->defaultValue('15.6')->end()
                        ->scalarNode('fetch')->defaultValue('2.0')->end()
                        ->enumNode('relay')->values(['modern', 'classic'])->defaultValue('classic')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @param string $name
     */
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

    /**
     * @param string $name
     */
    private function addSecurityQuerySection($name, $disabledValue)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name, 'scalar');
        $node->beforeNormalization()
                ->ifTrue(function ($v) {
                    return is_string($v) && is_numeric($v);
                })
                ->then(function ($v) {
                    return (int) $v;
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
                    return is_int($v) && $v < 0;
                })
                ->thenInvalid('"overblog_graphql.security.'.$name.'" must be greater or equal to 0.')
            ->end()
        ;

        return $node;
    }
}
