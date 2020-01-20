<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection;

use GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\DependencyInjection\Compiler\ConfigParserPass;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\EventListener\ErrorLoggerListener;
use Overblog\GraphQLBundle\Executor\Executor;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Resolver\FieldResolver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\EnumNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const NAME = 'overblog_graphql';

    /** bool */
    private $debug;

    /** null|string */
    private $cacheDir;

    /**
     * Constructor.
     *
     * @param bool        $debug    Whether to use the debug mode
     * @param string|null $cacheDir
     */
    public function __construct(bool $debug, string $cacheDir = null)
    {
        $this->debug = (bool) $debug;
        $this->cacheDir = $cacheDir;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::NAME);

        $rootNode = self::getRootNodeWithoutDeprecation($treeBuilder, self::NAME);

        $rootNode
            ->children()
                ->append($this->batchingMethodSection())
                ->append($this->definitionsSection())
                ->append($this->errorsHandlerSection())
                ->append($this->servicesSection())
                ->append($this->securitySection())
                ->append($this->doctrineSection())
            ->end();

        return $treeBuilder;
    }

    private function batchingMethodSection()
    {
        $builder = new TreeBuilder('batching_method', 'enum');
        /** @var EnumNodeDefinition $node */
        $node = self::getRootNodeWithoutDeprecation($builder, 'batching_method', 'enum');

        $node
            ->values(['relay', 'apollo'])
            ->defaultValue('relay')
        ->end();

        return $node;
    }

    private function errorsHandlerSection()
    {
        $builder = new TreeBuilder('errors_handler');
        /** @var ArrayNodeDefinition $node */
        $node = self::getRootNodeWithoutDeprecation($builder, 'errors_handler');
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
                ->end()
            ->end();

        return $node;
    }

    private function definitionsSection()
    {
        $builder = new TreeBuilder('definitions');
        /** @var ArrayNodeDefinition $node */
        $node = self::getRootNodeWithoutDeprecation($builder, 'definitions');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('argument_class')->defaultValue(Argument::class)->end()
                ->scalarNode('use_experimental_executor')->defaultFalse()->end()
                ->scalarNode('default_field_resolver')->defaultValue(FieldResolver::class)->end()
                ->scalarNode('class_namespace')->defaultValue('Overblog\\GraphQLBundle\\__DEFINITIONS__')->end()
                ->scalarNode('cache_dir')->defaultNull()->end()
                ->scalarNode('cache_dir_permissions')->defaultNull()->end()
                ->booleanNode('use_classloader_listener')->defaultTrue()->end()
                ->scalarNode('auto_compile')->defaultTrue()->end()
                ->booleanNode('show_debug_info')->info('Show some performance stats in extensions')->defaultFalse()->end()
                ->booleanNode('config_validation')->defaultValue($this->debug)->end()
                ->append($this->definitionsSchemaSection())
                ->append($this->definitionsMappingsSection())
                ->arrayNode('builders')
                    ->children()
                        ->append($this->builderSection('field'))
                        ->append($this->builderSection('fields'))
                        ->append($this->builderSection('args'))
                    ->end()
                ->end()

            ->end()
        ->end();

        return $node;
    }

    private function servicesSection()
    {
        $builder = new TreeBuilder('services');
        /** @var ArrayNodeDefinition $node */
        $node = self::getRootNodeWithoutDeprecation($builder, 'services');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('executor')
                    ->defaultValue(Executor::class)
                ->end()
                ->scalarNode('promise_adapter')
                    ->defaultValue(SyncPromiseAdapter::class)
                ->end()
                ->scalarNode('expression_language')
                    ->defaultValue(ExpressionLanguage::class)
                ->end()
                ->scalarNode('cache_expression_language_parser')->end()
            ->end()
        ->end();

        return $node;
    }

    private function securitySection()
    {
        $builder = new TreeBuilder('security');
        /** @var ArrayNodeDefinition $node */
        $node = self::getRootNodeWithoutDeprecation($builder, 'security');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->append($this->securityQuerySection('query_max_depth', QueryDepth::DISABLED))
                ->append($this->securityQuerySection('query_max_complexity', QueryComplexity::DISABLED))
                ->booleanNode('enable_introspection')->defaultTrue()->end()
                ->booleanNode('handle_cors')->defaultFalse()->end()
            ->end()
        ->end();

        return $node;
    }

    private function definitionsSchemaSection()
    {
        $builder = new TreeBuilder('schema');
        /** @var ArrayNodeDefinition $node */
        $node = self::getRootNodeWithoutDeprecation($builder, 'schema');
        $node
            ->beforeNormalization()
                ->ifTrue(function ($v) {
                    return isset($v['query']) && \is_string($v['query']) || isset($v['mutation']) && \is_string($v['mutation']);
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
                    ->arrayNode('resolver_maps')
                        ->defaultValue([])
                        ->prototype('scalar')->end()
                        ->setDeprecated('The "%path%.%node%" configuration is deprecated since version 0.13 and will be removed in 0.14. Add the "overblog_graphql.resolver_map" tag to the services instead.')
                    ->end()
                    ->arrayNode('types')
                        ->defaultValue([])
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }

    private function definitionsMappingsSection()
    {
        $builder = new TreeBuilder('mappings');
        $node = self::getRootNodeWithoutDeprecation($builder, 'mappings');
        $node
            ->children()
                ->arrayNode('auto_discover')
                    ->treatFalseLike(['bundles' => false, 'root_dir' => false])
                    ->treatTrueLike(['bundles' => true, 'root_dir' => true])
                    ->treatNullLike(['bundles' => true, 'root_dir' => true])
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('bundles')->defaultFalse()->end()
                        ->booleanNode('root_dir')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('types')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->beforeNormalization()
                            ->ifTrue(function ($v) {
                                return isset($v['type']) && \is_string($v['type']);
                            })
                            ->then(function ($v) {
                                if ('yml' === $v['type']) {
                                    $v['types'] = ['yaml'];
                                } else {
                                    $v['types'] = [$v['type']];
                                }
                                unset($v['type']);

                                return $v;
                            })
                        ->end()
                        ->children()
                            ->arrayNode('types')
                                ->prototype('enum')->values(\array_keys(ConfigParserPass::SUPPORTED_TYPES_EXTENSIONS))->isRequired()->end()
                            ->end()
                            ->scalarNode('dir')->defaultNull()->end()
                            ->scalarNode('suffix')->defaultValue(ConfigParserPass::DEFAULT_TYPES_SUFFIX)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function doctrineSection()
    {
        $builder = new TreeBuilder('doctrine');
        /** @var ArrayNodeDefinition $node */
        $node = self::getRootNodeWithoutDeprecation($builder, 'doctrine');
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('types_mapping')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    /**
     * @param string $name
     *
     * @return ArrayNodeDefinition
     */
    private function builderSection($name)
    {
        $builder = new TreeBuilder($name);
        /** @var ArrayNodeDefinition $node */
        $node = self::getRootNodeWithoutDeprecation($builder, $name);
        $node->beforeNormalization()
            ->ifTrue(function ($v) {
                return \is_array($v) && !empty($v);
            })
            ->then(function ($v) {
                foreach ($v as $key => &$config) {
                    if (\is_string($config)) {
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
        $builder = new TreeBuilder($name, 'scalar');
        /** @var ScalarNodeDefinition $node */
        $node = self::getRootNodeWithoutDeprecation($builder, $name, 'scalar');
        $node->beforeNormalization()
                ->ifTrue(function ($v) {
                    return \is_string($v) && \is_numeric($v);
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
            ->defaultValue($disabledValue)
            ->validate()
                ->ifTrue(function ($v) {
                    return \is_int($v) && $v < 0;
                })
                ->thenInvalid(\sprintf('"%s.security.%s" must be greater or equal to 0.', self::NAME, $name))
            ->end()
        ;

        return $node;
    }

    /**
     * @internal
     *
     * @param TreeBuilder $builder
     * @param string|null $name
     * @param string      $type
     *
     * @return ArrayNodeDefinition|\Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    public static function getRootNodeWithoutDeprecation(TreeBuilder $builder, string $name, string $type = 'array')
    {
        // BC layer for symfony/config 4.1 and older
        return \method_exists($builder, 'getRootNode') ? $builder->getRootNode() : $builder->root($name, $type);
    }
}
