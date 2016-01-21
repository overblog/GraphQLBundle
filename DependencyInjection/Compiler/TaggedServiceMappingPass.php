<?php

namespace Overblog\GraphBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class TaggedServiceMappingPass implements CompilerPassInterface
{
    private function getTaggedServiceMapping(ContainerBuilder $container, $tagName)
    {
        $serviceMapping = [];

        $taggedServices = $container->findTaggedServiceIds($tagName);

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['alias']) && is_string($tag['alias'])) {
                    throw new \InvalidArgumentException(
                        sprintf('Service tagged "%s" must have valid "alias" argument.', $tag)
                    );
                }
                $serviceMapping[$tag['alias']] = $id;
            }
        }
        return $serviceMapping;
    }

    private function taggedServiceToParameterMapping(ContainerBuilder $container, $tagName, $parameterName)
    {
        $container->setParameter($parameterName, $this->getTaggedServiceMapping($container, $tagName));
    }

    public function process(ContainerBuilder $container)
    {
        $this->taggedServiceToParameterMapping($container, $this->getTagName(), $this->getParameterName());
    }

    abstract protected function getTagName();

    abstract protected function getParameterName();
}
