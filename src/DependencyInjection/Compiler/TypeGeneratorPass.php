<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\Builder\TypeFactory;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TypeGeneratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $generatedClasses = $container->get('overblog_graphql.cache_compiler')
            ->compile(TypeGenerator::MODE_MAPPING_ONLY);

        foreach ($generatedClasses as $class => $file) {
            $alias = \preg_replace('/Type$/', '', \substr(\strrchr($class, '\\'), 1));
            $this->setTypeServiceDefinition($container, $class, $alias);
        }
    }

    private function setTypeServiceDefinition(ContainerBuilder $container, $class, $alias): void
    {
        $definition = $container->register($class);
        $definition->setFactory([new Reference(TypeFactory::class), 'create']);
        $definition->setPublic(false);
        $definition->setArguments([$class]);
        $definition->addTag(TypeTaggedServiceMappingPass::TAG_NAME, ['alias' => $alias, 'generated' => true]);
    }
}
