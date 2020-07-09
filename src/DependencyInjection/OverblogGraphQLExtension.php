<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection;

use Closure;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\CacheWarmer\CompileCacheWarmer;
use Overblog\GraphQLBundle\Config\Processor\BuilderProcessor;
use Overblog\GraphQLBundle\Definition\Builder\SchemaBuilder;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Error\ExceptionConverter;
use Overblog\GraphQLBundle\Error\ExceptionConverterInterface;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\EventListener\ClassLoaderListener;
use Overblog\GraphQLBundle\EventListener\DebugListener;
use Overblog\GraphQLBundle\EventListener\ErrorHandlerListener;
use Overblog\GraphQLBundle\EventListener\ErrorLoggerListener;
use Overblog\GraphQLBundle\Request\Executor;
use Overblog\GraphQLBundle\Validator\ValidatorFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use function array_fill_keys;
use function class_exists;
use function realpath;
use function sprintf;

class OverblogGraphQLExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->loadConfigFiles($container);
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $this->setBatchingMethod($config, $container);
        $this->setServicesAliases($config, $container);
        $this->setSchemaBuilderArguments($config, $container);
        $this->setSchemaArguments($config, $container);
        $this->setErrorHandler($config, $container);
        $this->setSecurity($config, $container);
        $this->setConfigBuilders($config, $container);
        $this->setDebugListener($config, $container);
        $this->setDefinitionParameters($config, $container);
        $this->setProfilerParameters($config, $container);
        $this->setClassLoaderListener($config, $container);
        $this->setCompilerCacheWarmer($config, $container);
        $this->registerForAutoconfiguration($container);
        $this->setDefaultFieldResolver($config, $container);
        $this->registerValidatorFactory($container);

        $container->setParameter($this->getAlias().'.config', $config);
        $container->setParameter($this->getAlias().'.resources_dir', realpath(__DIR__.'/../Resources'));
    }

    public function getAlias()
    {
        return Configuration::NAME;
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration(
            $container->getParameter('kernel.debug'),
            $container->hasParameter('kernel.cache_dir') ? $container->getParameter('kernel.cache_dir') : null
        );
    }

    private function loadConfigFiles(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('commands.yaml');
        $loader->load('listeners.yaml');
        $loader->load('graphql_types.yaml');
        $loader->load('graphql_resolvers.yaml');
        $loader->load('expression_language_functions.yaml');
        $loader->load('definition_config_processors.yaml');
        $loader->load('aliases.yaml');
        $loader->load('profiler.yaml');
    }

    private function registerForAutoconfiguration(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(MutationInterface::class)
            ->addTag('overblog_graphql.mutation');

        $container->registerForAutoconfiguration(ResolverInterface::class)
            ->addTag('overblog_graphql.resolver');

        $container->registerForAutoconfiguration(Type::class)
            ->addTag('overblog_graphql.type');
    }

    private function registerValidatorFactory(ContainerBuilder $container): void
    {
        if (class_exists('Symfony\\Component\\Validator\\Validation')) {
            $container->register(ValidatorFactory::class)
                ->setArguments([
                    new Reference('validator.validator_factory'),
                    new Reference('translator.default', $container::NULL_ON_INVALID_REFERENCE),
                ])
                ->addTag(
                    'overblog_graphql.global_variable',
                    [
                        'alias' => 'validatorFactory',
                        'public' => false,
                    ]
                );
        }
    }

    private function setDefaultFieldResolver(array $config, ContainerBuilder $container): void
    {
        $container->setAlias($this->getAlias().'.default_field_resolver', $config['definitions']['default_field_resolver']);
    }

    private function setCompilerCacheWarmer(array $config, ContainerBuilder $container): void
    {
        $container->register(CompileCacheWarmer::class)
            ->setArguments([
                new Reference($this->getAlias().'.cache_compiler'),
                $config['definitions']['auto_compile'],
            ])
            ->addTag('kernel.cache_warmer', ['priority' => 50])
        ;
    }

    private function setClassLoaderListener(array $config, ContainerBuilder $container): void
    {
        $container->setParameter($this->getAlias().'.use_classloader_listener', $config['definitions']['use_classloader_listener']);
        if ($config['definitions']['use_classloader_listener']) {
            $definition = $container->register(
                $this->getAlias().'.event_listener.classloader_listener',
                ClassLoaderListener::class
            );
            $definition->setPublic(true);
            $definition->setArguments([new Reference($this->getAlias().'.cache_compiler')]);
            $definition->addTag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'load', 'priority' => 255]);
            $definition->addTag('kernel.event_listener', ['event' => 'console.command', 'method' => 'load', 'priority' => 255]);
        }
    }

    private function setDefinitionParameters(array $config, ContainerBuilder $container): void
    {
        // generator and config
        $container->setParameter($this->getAlias().'.class_namespace', $config['definitions']['class_namespace']);
        $container->setParameter($this->getAlias().'.cache_dir', $config['definitions']['cache_dir']);
        $container->setParameter($this->getAlias().'.cache_dir_permissions', $config['definitions']['cache_dir_permissions']);
        $container->setParameter($this->getAlias().'.argument_class', $config['definitions']['argument_class']);
        $container->setParameter($this->getAlias().'.use_experimental_executor', $config['definitions']['use_experimental_executor']);
    }

    private function setProfilerParameters(array $config, ContainerBuilder $container): void
    {
        $container->setParameter($this->getAlias().'.profiler.query_match', $config['profiler']['query_match']);
    }

    private function setBatchingMethod(array $config, ContainerBuilder $container): void
    {
        $container->setParameter($this->getAlias().'.batching_method', $config['batching_method']);
    }

    private function setDebugListener(array $config, ContainerBuilder $container): void
    {
        if ($config['definitions']['show_debug_info']) {
            $definition = $container->register(DebugListener::class);
            $definition->addTag('kernel.event_listener', ['event' => Events::PRE_EXECUTOR, 'method' => 'onPreExecutor']);
            $definition->addTag('kernel.event_listener', ['event' => Events::POST_EXECUTOR, 'method' => 'onPostExecutor']);
        }
    }

    private function setConfigBuilders(array $config, ContainerBuilder $container): void
    {
        foreach (BuilderProcessor::BUILDER_TYPES as $type) {
            if (!empty($config['definitions']['builders'][$type])) {
                foreach ($config['definitions']['builders'][$type] as $params) {
                    $container->addObjectResource($params['class']);
                    BuilderProcessor::addBuilderClass($params['alias'], $type, $params['class']);
                }
            }
        }
    }

    private function setSecurity(array $config, ContainerBuilder $container): void
    {
        $executorDefinition = $container->getDefinition(Executor::class);
        if ($config['security']['enable_introspection']) {
            $executorDefinition->addMethodCall('enableIntrospectionQuery');
        } else {
            $executorDefinition->addMethodCall('disableIntrospectionQuery');
        }

        foreach ($config['security'] as $key => $value) {
            $container->setParameter(sprintf('%s.%s', $this->getAlias(), $key), $value);
        }
    }

    private function setErrorHandler(array $config, ContainerBuilder $container): void
    {
        if (!$config['errors_handler']['enabled']) {
            return;
        }

        $container->register(ExceptionConverter::class)
            ->setArgument(0, $this->buildExceptionMap($config['errors_handler']['exceptions']))
            ->setArgument(1, $config['errors_handler']['map_exceptions_to_parent']);

        $container->register(ErrorHandler::class)
            ->setArgument(0, new Reference(EventDispatcherInterface::class))
            ->setArgument(1, new Reference(ExceptionConverterInterface::class));

        $container->register(ErrorHandlerListener::class)
            ->setArgument(0, new Reference(ErrorHandler::class))
            ->setArgument(1, $config['errors_handler']['rethrow_internal_exceptions'])
            ->setArgument(2, $config['errors_handler']['debug'])
            ->addTag('kernel.event_listener', ['event' => Events::POST_EXECUTOR, 'method' => 'onPostExecutor']);

        $container->setAlias(ExceptionConverterInterface::class, ExceptionConverter::class);

        if ($config['errors_handler']['log']) {
            $loggerServiceId = $config['errors_handler']['logger_service'];
            $invalidBehavior = ErrorLoggerListener::DEFAULT_LOGGER_SERVICE === $loggerServiceId ? ContainerInterface::NULL_ON_INVALID_REFERENCE : ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

            $container->register(ErrorLoggerListener::class)
                ->setPublic(true)
                ->addArgument(new Reference($loggerServiceId, $invalidBehavior))
                ->addTag('kernel.event_listener', ['event' => Events::ERROR_FORMATTING, 'method' => 'onErrorFormatting']);
        }
    }

    private function setSchemaBuilderArguments(array $config, ContainerBuilder $container): void
    {
        $container->getDefinition(SchemaBuilder::class)
            ->replaceArgument(1, $config['definitions']['config_validation']);
    }

    private function setSchemaArguments(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['definitions']['schema'])) {
            return;
        }

        $executorDefinition = $container->getDefinition(Executor::class);
        $resolverMapsBySchema = [];

        foreach ($config['definitions']['schema'] as $schemaName => $schemaConfig) {
            // builder
            $schemaBuilderID = sprintf('%s.schema_builder_%s', $this->getAlias(), $schemaName);
            $definition = $container->register($schemaBuilderID, Closure::class);
            $definition->setFactory([new Reference('overblog_graphql.schema_builder'), 'getBuilder']);
            $definition->setArguments([
                $schemaName,
                $schemaConfig['query'],
                $schemaConfig['mutation'],
                $schemaConfig['subscription'],
                $schemaConfig['types'],
            ]);
            // schema
            $schemaID = sprintf('%s.schema_%s', $this->getAlias(), $schemaName);
            $definition = $container->register($schemaID, Schema::class);
            $definition->setFactory([new Reference($schemaBuilderID), 'call']);

            $executorDefinition->addMethodCall('addSchemaBuilder', [$schemaName, new Reference($schemaBuilderID)]);

            $resolverMapsBySchema[$schemaName] = array_fill_keys($schemaConfig['resolver_maps'], 0);
        }

        $container->setParameter(sprintf('%s.resolver_maps', $this->getAlias()), $resolverMapsBySchema);
    }

    private function setServicesAliases(array $config, ContainerBuilder $container): void
    {
        if (isset($config['services'])) {
            foreach ($config['services'] as $name => $id) {
                $alias = sprintf('%s.%s', $this->getAlias(), $name);
                $container->setAlias($alias, $id);
            }
        }
    }

    /**
     * Returns a list of custom exceptions mapped to error/warning classes.
     *
     * @param array<string, string[]> $exceptionConfig
     *
     * @return array<string, string> Custom exception map, [exception => UserError/UserWarning]
     */
    private function buildExceptionMap(array $exceptionConfig): array
    {
        $exceptionMap = [];
        $errorsMapping = [
            'errors' => UserError::class,
            'warnings' => UserWarning::class,
        ];

        foreach ($exceptionConfig as $type => $exceptionList) {
            foreach ($exceptionList as $exception) {
                $exceptionMap[$exception] = $errorsMapping[$type];
            }
        }

        return $exceptionMap;
    }
}
