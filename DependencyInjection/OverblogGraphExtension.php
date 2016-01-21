<?php

namespace Overblog\GraphBundle\DependencyInjection;

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

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);


        if (isset($config['definitions']['fields'])) {
            $typeBuilder = $container->get('overblog_graph.field_builder');

            foreach($config['definitions']['fields'] as $name => $typeDefinition) {
                $customTypeId = sprintf('overblog_graph.definition.custom_%s_field', $container->underscore($name));

                $typeDefinition['config']['name'] = $name;

                $class = $typeBuilder->getClassBaseField($typeDefinition['type']);

                $container
                    ->setDefinition($customTypeId, new Definition($class))
                    ->setFactory([ new Reference('overblog_graph.field_builder'), 'create' ])
                    ->setArguments([$typeDefinition['type'], $typeDefinition['config']])
                    ->addTag('overblog_graph.field', ['alias' => $name])
                    ->setPublic(true)
                ;
            }
        }

        if (isset($config['definitions']['types'])) {
            $typeBuilder = $container->get('overblog_graph.type_builder');

            foreach($config['definitions']['types'] as $name => $typeDefinition) {
                $customTypeId = sprintf('overblog_graph.definition.custom_%s_type', $container->underscore($name));

                $typeDefinition['config']['name'] = $name;

                $class = $typeBuilder->getClassBaseType($typeDefinition['type']);

                $container
                    ->setDefinition($customTypeId, new Definition($class))
                    ->setFactory([ new Reference('overblog_graph.type_builder'), 'create' ])
                    ->setArguments([$typeDefinition['type'], $typeDefinition['config']])
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
