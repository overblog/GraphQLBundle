<?php

namespace Overblog\GraphQLBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OverblogGraphQLExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('graphql_types.yml');
        $loader->load('graphql_fields.yml');
        $loader->load('graphql_args.yml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['services'])) {
            foreach ($config['services'] as $name => $id) {
                $alias = sprintf('%s.%s', $this->getAlias(), $name);
                $container->setAlias($alias, $id);
            }
        }

        if (isset($config['definitions']['types'])) {
            $builderId = $this->getAlias() . '.type_builder';

            foreach($config['definitions']['types'] as $name => $options) {
                $customTypeId = sprintf('%s.definition.custom_%s_type', $this->getAlias(), $container->underscore($name));

                $options['config']['name'] = $name;

                $container
                    ->setDefinition($customTypeId, new Definition('GraphQL\\Type\\Definition\\Type'))
                    ->setFactory([ new Reference($builderId), 'create' ])
                    ->setArguments([$options['type'], $options['config']])
                    ->addTag($this->getAlias() . '.type', ['alias' => $name])
                ;
            }
        }

        $container->getDefinition($this->getAlias() . '.schema_builder')
            ->replaceArgument(2, $config['definitions']['config_validation']);

        if (isset($config['definitions']['schema'])) {
            $container
                ->getDefinition($this->getAlias(). '.schema')
                ->replaceArgument(0, $config['definitions']['schema']['query'])
                ->replaceArgument(1, $config['definitions']['schema']['mutation'])
                ->replaceArgument(2, $config['definitions']['schema']['subscription'])
                ->setPublic(true)
            ;
        }
    }

    public function getAlias()
    {
        return 'overblog_graphql';
    }
}
