<?php

namespace Overblog\GraphQLBundle\DependencyInjection;

use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\CacheWarmer\CompileCacheWarmer;
use Overblog\GraphQLBundle\Config\TypeWithOutputFieldsDefinition;
use Overblog\GraphQLBundle\EventListener\ClassLoaderListener;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ParserCache\ArrayParserCache;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

class OverblogGraphQLExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('graphql_types.yml');

        $config = $this->treatConfigs($configs, $container);

        $this->setBatchingMethod($config, $container);
        $this->setExpressionLanguageDefaultParser($container);
        $this->setServicesAliases($config, $container);
        $this->setSchemaBuilderArguments($config, $container);
        $this->setSchemaArguments($config, $container);
        $this->setErrorHandlerArguments($config, $container);
        $this->setGraphiQLTemplate($config, $container);
        $this->setSecurity($config, $container);
        $this->setConfigBuilders($config, $container);
        $this->setVersions($config, $container);
        $this->setShowDebug($config, $container);
        $this->setDefinitionParameters($config, $container);
        $this->setClassLoaderListener($config, $container);
        $this->setCompilerCacheWarmer($config, $container);

        $container->setParameter($this->getAlias().'.resources_dir', realpath(__DIR__.'/../Resources'));
    }

    public function prepend(ContainerBuilder $container)
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
        return 'overblog_graphql';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration(
            $container->getParameter('kernel.debug'),
            $container->hasParameter('kernel.cache_dir') ? $container->getParameter('kernel.cache_dir') : null
        );
    }

    private function setCompilerCacheWarmer(array $config, ContainerBuilder $container)
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

    private function setClassLoaderListener(array $config, ContainerBuilder $container)
    {
        $container->setParameter($this->getAlias().'.use_classloader_listener', $config['definitions']['use_classloader_listener']);
        if ($config['definitions']['use_classloader_listener']) {
            $definition = $container->setDefinition(
                $this->getAlias().'.event_listener.classloader_listener',
                new Definition(ClassLoaderListener::class)
            );
            $definition->setArguments([new Reference($this->getAlias().'.cache_compiler')]);
            $definition->addTag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'load', 'priority' => 255]);
            $definition->addTag('kernel.event_listener', ['event' => 'console.command', 'method' => 'load', 'priority' => 255]);
        }
    }

    private function setDefinitionParameters(array $config, ContainerBuilder $container)
    {
        // auto mapping
        $container->setParameter($this->getAlias().'.auto_mapping.enabled', $config['definitions']['auto_mapping']['enabled']);
        $container->setParameter($this->getAlias().'.auto_mapping.directories', $config['definitions']['auto_mapping']['directories']);
        // generator and config
        $container->setParameter($this->getAlias().'.default_resolver', $config['definitions']['default_resolver']);
        $container->setParameter($this->getAlias().'.class_namespace', $config['definitions']['class_namespace']);
        $container->setParameter($this->getAlias().'.cache_dir', $config['definitions']['cache_dir']);
    }

    private function setBatchingMethod(array $config, ContainerBuilder $container)
    {
        $container->setParameter($this->getAlias().'.batching_method', $config['batching_method']);
    }

    private function setExpressionLanguageDefaultParser(ContainerBuilder $container)
    {
        $class = version_compare(Kernel::VERSION, '3.2.0', '>=') ? ArrayAdapter::class : ArrayParserCache::class;
        $definition = new Definition($class);
        $definition->setPublic(false);
        $container->setDefinition($this->getAlias().'.cache_expression_language_parser.default', $definition);
    }

    private function setShowDebug(array $config, ContainerBuilder $container)
    {
        $container->getDefinition($this->getAlias().'.request_executor')->replaceArgument(4, $config['definitions']['show_debug_info']);
    }

    private function setVersions(array $config, ContainerBuilder $container)
    {
        foreach ($config['versions'] as $key => $version) {
            $container->setParameter(sprintf('%s.versions.%s', $this->getAlias(), $key), $version);
        }
    }

    private function setConfigBuilders(array $config, ContainerBuilder $container)
    {
        $useObjectToAddResource = method_exists($container, 'addObjectResource');
        $objectToAddResourceMethod = $useObjectToAddResource ? 'addObjectResource' : 'addClassResource';

        foreach (['args', 'field'] as $category) {
            if (!empty($config['definitions']['builders'][$category])) {
                $method = 'add'.ucfirst($category).'BuilderClass';

                foreach ($config['definitions']['builders'][$category] as $params) {
                    $object = $useObjectToAddResource ? $params['class'] : new \ReflectionClass($params['class']);
                    $container->$objectToAddResourceMethod($object);
                    TypeWithOutputFieldsDefinition::$method($params['alias'], $params['class']);
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

    private function setSecurity(array $config, ContainerBuilder $container)
    {
        foreach ($config['security'] as $key => $value) {
            $container->setParameter(sprintf('%s.%s', $this->getAlias(), $key), $value);
        }
    }

    private function setGraphiQLTemplate(array $config, ContainerBuilder $container)
    {
        $container->setParameter($this->getAlias().'.graphiql_template', $config['templates']['graphiql']);
    }

    private function setErrorHandlerArguments(array $config, ContainerBuilder $container)
    {
        $errorHandlerDefinition = $container->getDefinition($this->getAlias().'.error_handler');

        if (isset($config['definitions']['internal_error_message'])) {
            $errorHandlerDefinition->replaceArgument(0, $config['definitions']['internal_error_message']);
        }

        if (isset($config['definitions']['map_exceptions_to_parent'])) {
            $errorHandlerDefinition->replaceArgument(
                3,
                $config['definitions']['map_exceptions_to_parent']
            );
        }

        if (isset($config['definitions']['exceptions'])) {
            $errorHandlerDefinition
                ->replaceArgument(2, $this->buildExceptionMap($config['definitions']['exceptions']))
                ->addMethodCall('setUserWarningClass', [$config['definitions']['exceptions']['types']['warnings']])
                ->addMethodCall('setUserErrorClass', [$config['definitions']['exceptions']['types']['errors']])
            ;
        }
    }

    private function setSchemaBuilderArguments(array $config, ContainerBuilder $container)
    {
        $container->getDefinition($this->getAlias().'.schema_builder')
            ->replaceArgument(1, $config['definitions']['config_validation']);
    }

    private function setSchemaArguments(array $config, ContainerBuilder $container)
    {
        if (isset($config['definitions']['schema'])) {
            $executorDefinition = $container->getDefinition($this->getAlias().'.request_executor');

            foreach ($config['definitions']['schema'] as $schemaName => $schemaConfig) {
                $schemaID = sprintf('%s.schema_%s', $this->getAlias(), $schemaName);
                $definition = new Definition(Schema::class);
                $definition->setFactory([new Reference('overblog_graphql.schema_builder'), 'create']);
                $definition->setArguments([$schemaConfig['query'], $schemaConfig['mutation'], $schemaConfig['subscription']]);
                $definition->setPublic(false);
                $container->setDefinition($schemaID, $definition);

                $executorDefinition->addMethodCall('addSchema', [$schemaName, new Reference($schemaID)]);
            }
        }
    }

    private function setServicesAliases(array $config, ContainerBuilder $container)
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
     * @param array $exceptionConfig
     *
     * @return array Custom exception map, [exception => UserError/UserWarning]
     */
    private function buildExceptionMap(array $exceptionConfig)
    {
        $exceptionMap = [];
        $typeMap = $exceptionConfig['types'];

        foreach ($exceptionConfig as $type => $exceptionList) {
            if ('types' === $type) {
                continue;
            }

            foreach ($exceptionList as $exception) {
                $exceptionMap[$exception] = $typeMap[$type];
            }
        }

        return $exceptionMap;
    }
}
