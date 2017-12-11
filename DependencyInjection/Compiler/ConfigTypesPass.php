<?php

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ConfigTypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $generatedClasses = $container->get('overblog_graphql.cache_compiler')
            ->compile(TypeGenerator::MODE_MAPPING_ONLY);

        foreach ($generatedClasses as $class => $file) {
            $aliases = [preg_replace('/Type$/', '', substr(strrchr($class, '\\'), 1))];
            $this->setTypeServiceDefinition($container, $class, $aliases);
        }
    }

    private function setTypeServiceDefinition(ContainerBuilder $container, $class, array $aliases)
    {
        $definition = $container->setDefinition($class, new Definition($class));
        $definition->setPublic(false);
        $definition->setArguments([new Reference('service_container')]);
        foreach ($aliases as $alias) {
            $definition->addTag(TypeTaggedServiceMappingPass::TAG_NAME, ['alias' => $alias, 'generated' => true]);
        }
    }
}
