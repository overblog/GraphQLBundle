<?php

namespace Overblog\GraphBundle\DependencyInjection;

use GraphQL\Type\Definition\Config;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OverblogGraphExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('graphql_types.yml');
        $loader->load('graphql_fields.yml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $config['definitions']['config_validation'] ? Config::enableValidation() : Config::disableValidation();

        if (isset($config['definitions']['types'])) {
            $builderId = 'overblog_graph.type_builder';
            $builder = $container->get($builderId);

            foreach($config['definitions']['types'] as $name => $options) {
                $customTypeId = sprintf('overblog_graph.definition.custom_%s_type', $container->underscore($name));

                $options['config']['name'] = $name;

                $class = $builder->getBaseClassName($options['type']);

                $container
                    ->setDefinition($customTypeId, new Definition($class))
                    ->setFactory([ new Reference($builderId), 'create' ])
                    ->setArguments([$options['type'], $options['config']])
                    ->addTag('overblog_graph.type', ['alias' => $name])
                    ->setPublic(true)
                ;
            }
        }

        if (isset($config['definitions']['schema'])) {
            $container
                ->getDefinition('overblog_graph.schema')
                ->replaceArgument(0, $config['definitions']['schema']['query'])
                ->replaceArgument(1, $config['definitions']['schema']['mutation'])
                ->setPublic(true)
            ;
        }
    }
}
