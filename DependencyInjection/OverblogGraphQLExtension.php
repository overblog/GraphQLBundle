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

use Overblog\GraphQLBundle\Config\TypeWithOutputFieldsDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OverblogGraphQLExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('graphql_types.yml');
        $loader->load('graphql_resolvers.yml');

        $config = $this->treatConfigs($configs, $container);

        $this->setServicesAliases($config, $container);
        $this->setSchemaBuilderArguments($config, $container);
        $this->setSchemaArguments($config, $container);
        $this->setErrorHandlerArguments($config, $container);
        $this->setGraphiQLTemplate($config, $container);
        $this->setSecurity($config, $container);
        $this->setConfigBuilders($config);

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

    private function setConfigBuilders(array $config)
    {
        foreach (['args', 'field'] as $category) {
            if (!empty($config['definitions']['builders'][$category])) {
                $method = 'add'.ucfirst($category).'BuilderClass';

                foreach ($config['definitions']['builders'][$category] as $params) {
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
        $container->setParameter($this->getAlias().'.query_max_depth', $config['security']['query_max_depth']);
        $container->setParameter($this->getAlias().'.query_max_complexity', $config['security']['query_max_complexity']);
    }

    private function setGraphiQLTemplate(array $config, ContainerBuilder $container)
    {
        $container->setParameter($this->getAlias().'.graphiql_template', $config['templates']['graphiql']);
    }

    private function setErrorHandlerArguments(array $config, ContainerBuilder $container)
    {
        if (isset($config['definitions']['internal_error_message'])) {
            $container
                ->getDefinition($this->getAlias().'.error_handler')
                ->replaceArgument(0, $config['definitions']['internal_error_message'])
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
            $container
                ->getDefinition($this->getAlias().'.schema')
                ->replaceArgument(0, $config['definitions']['schema']['query'])
                ->replaceArgument(1, $config['definitions']['schema']['mutation'])
                ->replaceArgument(2, $config['definitions']['schema']['subscription'])
                ->setPublic(true)
            ;
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

    public function getAlias()
    {
        return 'overblog_graphql';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
