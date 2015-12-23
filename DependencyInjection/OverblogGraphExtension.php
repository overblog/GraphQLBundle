<?php

namespace Overblog\GraphBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OverblogGraphExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $def = $container->getDefinition('graph.type_builder');

        foreach ($config['types'] as $type => $class) {
            $def->addMethodCall('setDefinitionClass', [$type, $class]);
        }

        $container
            ->setDefinition('graph.schema', new DefinitionDecorator('graph.schema.abstract'))
            ->replaceArgument(0, $config['schema']['queries'])
            ->replaceArgument(1, $config['schema']['mutations']);
    }
}
