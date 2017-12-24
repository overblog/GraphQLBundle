<?php

namespace Overblog\GraphQLBundle\DependencyInjection;

use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\EventListener\ErrorLoggerListener;
use Overblog\GraphQLBundle\Resolver\Resolver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\EnumNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const NAME = 'overblog_graphql';

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
        $rootNode = $treeBuilder->root(self::NAME);

        $rootNode
            ->children()
                ->append($this->batchingMethodSection())
                ->append($this->definitionsSection())
                ->append($this->errorsHandlerSection())
                ->append($this->servicesSection())
                ->append($this->securitySection())
            ->end();

        return $treeBuilder;
    }

    private function batchingMethodSection()
    {
        $builder = new TreeBuilder();
        /** @var EnumNodeDefinition $node */
        $node = $builder->root('batching_method', 'enum');

        $node
            ->values(['relay', 'apollo'])
            ->defaultValue('relay')
        ->end();

        return $node;
    }

    private function errorsHandlerSection()
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('errors_handler');
        $node
            ->treatFalseLike(['enabled' => false])
            ->treatTrueLike(['enabled' => true])
            ->treatNullLike(['enabled' => true])
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->scalarNode('internal_error_message')->defaultValue(ErrorHandler::DEFAULT_ERROR_MESSAGE)->end()
                ->booleanNode('rethrow_internal_exceptions')->defaultFalse()->end()
                ->booleanNode('debug')->defaultValue($this->debug)->end()
                ->booleanNode('log')->defaultTrue()->end()
                ->scalarNode('logger_service')->defaultValue(ErrorLoggerListener::DEFAULT_LOGGER_SERVICE)->end()
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
            ->end();

        return $node;
    }

    private function definitionsSection()
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('definitions');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->variableNode('default_resolver')->defaultValue([Resolver::class, 'defaultResolveFn'])->end()
                ->scalarNode('class_namespace')->defaultValue('Overblog\\GraphQLBundle\\__DEFINITIONS__')->end()
                ->scalarNode('cache_dir')->defaultValue($this->cacheDir.'/overblog/graphql-bundle/__definitions__')->end()
                ->booleanNode('use_classloader_listener')->defaultTrue()->end()
                ->booleanNode('auto_compile')->defaultTrue()->end()
                ->booleanNode('show_debug_info')->info('Show some performance stats in extensions')->defaultFalse()->end()
                ->booleanNode('config_validation')->defaultValue($this->debug)->end()
                ->append($this->definitionsSchemaSection())
                ->append($this->definitionsAutoMappingSection())
                ->append($this->definitionsMappingsSection())
                ->arrayNode('builders')
                    ->children()
                        ->append($this->builderSection('field'))
                        ->append($this->builderSection('args'))
                    ->end()
                ->end()

            ->end()
        ->end();

        return $node;
    }

    private function servicesSection()
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('services');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('executor')
                    ->defaultValue(self::NAME.'.executor.default')
                ->end()
                ->scalarNode('promise_adapter')
                    ->defaultValue(self::NAME.'.promise_adapter.default')
                ->end()
                ->scalarNode('expression_language')
                    ->defaultValue(self::NAME.'.expression_language.default')
                ->end()
                ->scalarNode('cache_expression_language_parser')
                    ->defaultValue(self::NAME.'.cache_expression_language_parser.default')
                ->end()
            ->end()
        ->end();

        return $node;
    }

    private function securitySection()
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('security');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->append($this->securityQuerySection('query_max_depth', QueryDepth::DISABLED))
                ->append($this->securityQuerySection('query_max_complexity', QueryComplexity::DISABLED))
                ->booleanNode('handle_cors')->defaultFalse()->end()
            ->end()
        ->end();

        return $node;
    }

    private function definitionsSchemaSection()
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('schema');
        $node
            ->beforeNormalization()
                ->ifTrue(function ($v) {
                    return isset($v['query']) && is_string($v['query']) || isset($v['mutation']) && is_string($v['mutation']);
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
        ->end();

        return $node;
    }

    private function definitionsAutoMappingSection()
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('auto_mapping');
        $node
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
        ->end();

        return $node;
    }

    private function definitionsMappingsSection()
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $node */
        $node = $builder->root('mappings');
        $node
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
        ->end();

        return $node;
    }

    /**
     * @param string $name
     *
     * @return ArrayNodeDefinition
     */
    private function builderSection($name)
    {
        $builder = new TreeBuilder();
        /** @var ArrayNodeDefinition $node */
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
     * @param bool   $disabledValue
     *
     * @return ScalarNodeDefinition
     */
    private function securityQuerySection($name, $disabledValue)
    {
        $builder = new TreeBuilder();
        /** @var ScalarNodeDefinition $node */
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
                ->thenInvalid(sprintf('"%s.security.%s" must be greater or equal to 0.', self::NAME, $name))
            ->end()
        ;

        return $node;
    }
}
