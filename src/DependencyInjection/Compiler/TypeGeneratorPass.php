<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Exception;
use Overblog\GraphQLBundle\Definition\Builder\TypeFactory;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Generator\TypeBuilder;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Generator\TypeGeneratorOptions;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;

use function preg_replace;
use function strrchr;
use function substr;

final class TypeGeneratorPass implements CompilerPassInterface
{
    /**
     * @throws Exception
     */
    public function process(ContainerBuilder $container): void
    {
        // We construct the TypeGenerator manually so that we don't have to boot the container
        // while we are in compilation phase.
        // See https://github.com/overblog/GraphQLBundle/issues/899
        $typeGenerator = new TypeGenerator(
            $container->getParameter('overblog_graphql_types.config'),
            new TypeBuilder(
                new ExpressionConverter(new ExpressionLanguage()),
                $container->getParameter('overblog_graphql.class_namespace')
            ),
            new EventDispatcher(),
            new TypeGeneratorOptions(
                $container->getParameter('overblog_graphql.class_namespace'),
                $container->getParameter('overblog_graphql.cache_dir'),
                $container->getParameter('overblog_graphql.use_classloader_listener'),
                $container->getParameter('kernel.cache_dir'),
                $container->getParameter('overblog_graphql.cache_dir_permissions'),
            )
        );

        /**
         * @var array<class-string, string> $generatedClasses
         *
         * @phpstan-ignore-next-line
         */
        $generatedClasses = $typeGenerator->compile(TypeGenerator::MODE_MAPPING_ONLY);

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
