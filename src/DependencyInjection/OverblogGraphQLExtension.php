<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection;

use GraphQL\Error\UserError;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\CacheWarmer\CompileCacheWarmer;
use Overblog\GraphQLBundle\Config\Processor\BuilderProcessor;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\EventListener\ClassLoaderListener;
use Overblog\GraphQLBundle\EventListener\DebugListener;
use Overblog\GraphQLBundle\EventListener\ErrorHandlerListener;
use Overblog\GraphQLBundle\EventListener\ErrorLoggerListener;
use Overblog\GraphQLBundle\EventListener\TypeDecoratorListener;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OverblogGraphQLExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->loadConfigFiles($container);
        $config = $this->treatConfigs($configs, $container);

        $this->setBatchingMethod($config, $container);
        $this->setServicesAliases($config, $container);
        $this->setSchemaBuilderArguments($config, $container);
        $this->setSchemaArguments($config, $container);
        $this->setErrorHandler($config, $container);
        $this->setSecurity($config, $container);
        $this->setConfigBuilders($config, $container);
        $this->setDebugListener($config, $container);
        $this->setDefinitionParameters($config, $container);
        $this->setClassLoaderListener($config, $container);
        $this->setCompilerCacheWarmer($config, $container);

        $container->setParameter($this->getAlias().'.resources_dir', \realpath(__DIR__.'/../Resources'));
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $configs = $container->getParameterBag()->resolveValue($configs);
        $config = $this->treatConfigs($configs, $container, true);

        /** @var OverblogGraphQLTypesExtension $typesExtension */
        $typesExtension = $container->getExtension($this->getAlias().'_types');
        $typesExtension->containerPrependExtensionConfig($config, $container);
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
        $loader->load('services.yml');
        $loader->load('graphql_types.yml');
        $loader->load('graphql_resolvers.yml');
        $loader->load('expression_language_functions.yml');
        $loader->load('definition_config_processors.yml');
    }

    private function setCompilerCacheWarmer(array $config, ContainerBuilder $container): void
    {
        if ($config['definitions']['auto_compile']) {
            $definition = $container->setDefinition(
                CompileCacheWarmer::class,
                new Definition(CompileCacheWarmer::class)
            );
            $definition->setArguments([new Reference($this->getAlias().'.cache_compiler')]);
            $definition->addTag('kernel.cache_warmer', ['priority' => 50]);
        }
    }

    private function setClassLoaderListener(array $config, ContainerBuilder $container): void
    {
        $container->setParameter($this->getAlias().'.use_classloader_listener', $config['definitions']['use_classloader_listener']);
        if ($config['definitions']['use_classloader_listener']) {
            $definition = $container->setDefinition(
                $this->getAlias().'.event_listener.classloader_listener',
                new Definition(ClassLoaderListener::class)
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
        $container->setParameter($this->getAlias().'.default_resolver', $config['definitions']['default_resolver']);
        $container->setParameter($this->getAlias().'.class_namespace', $config['definitions']['class_namespace']);
        $container->setParameter($this->getAlias().'.cache_dir', $config['definitions']['cache_dir']);
    }

    private function setBatchingMethod(array $config, ContainerBuilder $container): void
    {
        $container->setParameter($this->getAlias().'.batching_method', $config['batching_method']);
    }

    private function setDebugListener(array $config, ContainerBuilder $container): void
    {
        if ($config['definitions']['show_debug_info']) {
            $definition = $container->setDefinition(
                DebugListener::class,
                new Definition(DebugListener::class)
            );
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

    private function treatConfigs(array $configs, ContainerBuilder $container, $forceReload = false)
    {
        static $config = null;

        if ($forceReload || null === $config) {
            $configuration = $this->getConfiguration($configs, $container);
            $config = $this->processConfiguration($configuration, $configs);
        }

        return $config;
    }

    private function setSecurity(array $config, ContainerBuilder $container): void
    {
        if (false === $config['security']['enable_introspection']) {
            $executorDefinition = $container->getDefinition($this->getAlias().'.request_executor');
            $executorDefinition->addMethodCall('disableIntrospectionQuery');
        }

        foreach ($config['security'] as $key => $value) {
            $container->setParameter(\sprintf('%s.%s', $this->getAlias(), $key), $value);
        }
    }

    private function setErrorHandler(array $config, ContainerBuilder $container): void
    {
        if ($config['errors_handler']['enabled']) {
            $id = $this->getAlias().'.error_handler';
            $errorHandlerDefinition = $container->setDefinition($id, new Definition(ErrorHandler::class));
            $errorHandlerDefinition->setPublic(false)
                ->setArguments(
                    [
                        new Reference('event_dispatcher'),
                        $config['errors_handler']['internal_error_message'],
                        $this->buildExceptionMap($config['errors_handler']['exceptions']),
                        $config['errors_handler']['map_exceptions_to_parent'],
                    ]
                )
            ;

            $errorHandlerListenerDefinition = $container->setDefinition(ErrorHandlerListener::class, new Definition(ErrorHandlerListener::class));
            $errorHandlerListenerDefinition->setPublic(true)
                ->setArguments([new Reference($id), new Reference(ErrorHandlerListener::DEFAULT_LOGGER_SERVICE), $config['errors_handler']['rethrow_internal_exceptions'], $config['errors_handler']['debug']])
                ->addTag('kernel.event_listener', ['event' => Events::POST_EXECUTOR, 'method' => 'onPostExecutor'])
            ;

            if ($config['errors_handler']['log']) {
                $loggerServiceId = $config['errors_handler']['logger_service'];
                $invalidBehavior = ErrorLoggerListener::DEFAULT_LOGGER_SERVICE === $loggerServiceId ? ContainerInterface::NULL_ON_INVALID_REFERENCE : ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
                $errorHandlerListenerDefinition = $container->setDefinition(ErrorLoggerListener::class, new Definition(ErrorLoggerListener::class));
                $errorHandlerListenerDefinition->setPublic(true)
                    ->setArguments([new Reference($loggerServiceId, $invalidBehavior)])
                    ->addTag('kernel.event_listener', ['event' => Events::ERROR_FORMATTING, 'method' => 'onErrorFormatting'])
                ;
            }
        }
    }

    private function setSchemaBuilderArguments(array $config, ContainerBuilder $container): void
    {
        $container->getDefinition($this->getAlias().'.schema_builder')
            ->replaceArgument(1, $config['definitions']['config_validation']);
    }

    private function setSchemaArguments(array $config, ContainerBuilder $container): void
    {
        if (isset($config['definitions']['schema'])) {
            $executorDefinition = $container->getDefinition($this->getAlias().'.request_executor');
            $typeDecoratorListenerDefinition = $container->getDefinition(TypeDecoratorListener::class);

            foreach ($config['definitions']['schema'] as $schemaName => $schemaConfig) {
                $schemaID = \sprintf('%s.schema_%s', $this->getAlias(), $schemaName);
                $definition = new Definition(Schema::class);
                $definition->setFactory([new Reference('overblog_graphql.schema_builder'), 'create']);
                $definition->setArguments([
                    $schemaName,
                    $schemaConfig['query'],
                    $schemaConfig['mutation'],
                    $schemaConfig['subscription'],
                    $schemaConfig['types'],
                ]);
                $definition->setPublic(false);
                $container->setDefinition($schemaID, $definition);

                if (!empty($schemaConfig['resolver_maps'])) {
                    $typeDecoratorListenerDefinition->addMethodCall(
                        'addSchemaResolverMaps',
                        [
                            $schemaName,
                            \array_map(function ($id) {
                                return new Reference($id);
                            }, $schemaConfig['resolver_maps']),
                        ]
                    );
                }
                $executorDefinition->addMethodCall('addSchema', [$schemaName, new Reference($schemaID)]);
            }
        }
    }

    private function setServicesAliases(array $config, ContainerBuilder $container): void
    {
        if (isset($config['services'])) {
            foreach ($config['services'] as $name => $id) {
                $alias = \sprintf('%s.%s', $this->getAlias(), $name);
                $container->setAlias($alias, $id);
            }
        }
    }

    /**
     * Returns a list of custom exceptions mapped to error/warning classes.
     *
     * @param array $exceptionConfig
     *
     * @return array Custom exception map, [exception => UserError/UserWarning]
     */
    private function buildExceptionMap(array $exceptionConfig)
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
