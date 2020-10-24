<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Exception;
use Overblog\GraphQLBundle\Definition\Builder\TypeFactory;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use function preg_replace;
use function strrchr;
use function substr;

class TypeGeneratorPass implements CompilerPassInterface
{
    /**
     * @throws Exception
     */
    public function process(ContainerBuilder $container): void
    {
        /**
         * @var array<class-string, string> $generatedClasses
         * @phpstan-ignore-next-line
         */
        $generatedClasses = $container->get('overblog_graphql.cache_compiler')
            ->compile(TypeGenerator::MODE_MAPPING_ONLY);

        foreach ($generatedClasses as $class => $file) {
            $portion = strrchr($class, '\\');

            if (false !== $portion) {
                $portion = substr($portion, 1);
            } else {
                $portion = $class;
            }

            $alias = preg_replace('/Type$/', '', $portion);
            $this->setTypeServiceDefinition($container, $class, $alias);
        }
    }

    private function setTypeServiceDefinition(ContainerBuilder $container, string $class, string $alias): void
    {
        $definition = $container->register($class);
        $definition->setFactory([new Reference(TypeFactory::class), 'create']);
        $definition->setPublic(false);
        $definition->setArguments([$class]);
        $definition->addTag(TypeTaggedServiceMappingPass::TAG_NAME, ['alias' => $alias, 'generated' => true]);
    }
}
