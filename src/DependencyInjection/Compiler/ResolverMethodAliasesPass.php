<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class ResolverMethodAliasesPass implements CompilerPassInterface
{
    private const SERVICE_SUBCLASS_TAG_MAPPING = [
        MutationInterface::class => 'overblog_graphql.mutation',
        ResolverInterface::class => 'overblog_graphql.resolver',
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            foreach (self::SERVICE_SUBCLASS_TAG_MAPPING as $tagName) {
                $this->addDefinitionTagsFromClassReflection($definition, $tagName);
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function addDefinitionTagsFromClassReflection(Definition $definition, string $tagName): void
    {
        if ($definition->hasTag($tagName)) {
            foreach ($definition->getTag($tagName) as $tag => $attributes) {
                if (!isset($attributes['method'])) {
                    $reflectionClass = new ReflectionClass($definition->getClass()); // @phpstan-ignore-line

                    if (!$reflectionClass->isAbstract()) {
                        $publicReflectionMethods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
                        $isAliased = $reflectionClass->implementsInterface(AliasedInterface::class);
                        foreach ($publicReflectionMethods as $publicReflectionMethod) {
                            if ('__construct' === $publicReflectionMethod->name || $isAliased && 'getAliases' === $publicReflectionMethod->name) {
                                continue;
                            }
                            $definition->addTag($tagName, ['method' => $publicReflectionMethod->name]);
                        }
                    }
                    continue;
                }
            }
        }
    }
}
