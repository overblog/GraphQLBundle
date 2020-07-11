<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use function array_map;
use function array_replace;
use function array_unique;
use function in_array;
use function is_string;
use function is_subclass_of;
use function sprintf;
use function trigger_error;
use const E_USER_DEPRECATED;

abstract class TaggedServiceMappingPass implements CompilerPassInterface
{
    private function getTaggedServiceMapping(ContainerBuilder $container, string $tagName): array
    {
        $serviceMapping = [];

        $taggedServices = $container->findTaggedServiceIds($tagName, true);
        $isType = TypeTaggedServiceMappingPass::TAG_NAME === $tagName;

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $this->checkRequirements($id, $attributes);
                $attributes = self::resolveAttributes($attributes, $id, !$isType);
                $solutionID = $id;

                if (!$isType && '__invoke' !== $attributes['method']) {
                    $solutionID = sprintf('%s::%s', $id, $attributes['method']);
                }

                if (!isset($serviceMapping[$solutionID])) {
                    $serviceMapping[$solutionID] = $attributes;
                }

                if (isset($attributes['alias']) && $solutionID !== $attributes['alias']) {
                    $serviceMapping[$solutionID]['aliases'][] = $attributes['alias'];
                }
            }
        }

        return $serviceMapping;
    }

    public function process(ContainerBuilder $container): void
    {
        $mapping = $this->getTaggedServiceMapping($container, $this->getTagName());
        $resolverDefinition = $container->findDefinition($this->getResolverServiceID());

        foreach ($mapping as $solutionID => $attributes) {
            $attributes['aliases'] = array_unique($attributes['aliases']);
            $aliases = $attributes['aliases'];
            $serviceID = $attributes['id'];

            $solutionDefinition = $container->findDefinition($serviceID);
            // make solution service public to improve lazy loading
            $solutionDefinition->setPublic(true);
            $this->autowireSolutionImplementingContainerAwareInterface($solutionDefinition, empty($attributes['generated']));

            $resolverDefinition->addMethodCall(
                'addSolution',
                [$solutionID, [[new Reference('service_container'), 'get'], [$serviceID]], $aliases, $attributes]
            );
        }
    }

    protected function checkRequirements(string $id, array $tag): void
    {
        if (isset($tag['alias']) && !is_string($tag['alias'])) {
            throw new InvalidArgumentException(
                sprintf('Service tagged "%s" must have valid "alias" argument.', $id)
            );
        }
    }

    private static function resolveAttributes(array $attributes, string $id, bool $withMethod): array
    {
        $default = ['id' => $id, 'aliases' => []];
        if ($withMethod) {
            $default['method'] = '__invoke';
        }
        $attributes = array_replace($default, $attributes);

        return $attributes;
    }

    private function autowireSolutionImplementingContainerAwareInterface(Definition $solutionDefinition, bool $isGenerated): void
    {
        $methods = array_map(
            function ($methodCall) {
                return $methodCall[0];
            },
            $solutionDefinition->getMethodCalls()
        );
        if (
            $isGenerated
            && is_subclass_of($solutionDefinition->getClass(), ContainerAwareInterface::class)
            && !in_array('setContainer', $methods)
        ) {
            @trigger_error(
                sprintf(
                    'Autowire method "%s::setContainer" for custom tagged (type, resolver or mutation) services is deprecated as of 0.9 and will be removed in 1.0.',
                    ContainerAwareInterface::class
                ),
                E_USER_DEPRECATED
            );
            $solutionDefinition->addMethodCall('setContainer', [new Reference('service_container')]);
        }
    }

    abstract protected function getTagName(): string;

    abstract protected function getResolverServiceID(): string;
}
