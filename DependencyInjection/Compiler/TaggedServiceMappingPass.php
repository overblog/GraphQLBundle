<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

abstract class TaggedServiceMappingPass implements CompilerPassInterface
{
    private function getTaggedServiceMapping(ContainerBuilder $container, $tagName)
    {
        $serviceMapping = [];

        $taggedServices = $container->findTaggedServiceIds($tagName);

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                $this->checkRequirements($id, $tag);
                $serviceMapping[$tag['alias']] = array_merge($tag, ['id' => $id]);
            }
        }

        return $serviceMapping;
    }

    public function process(ContainerBuilder $container)
    {
        $mapping = $this->getTaggedServiceMapping($container, $this->getTagName());
        $container->setParameter($this->getParameterName(), $mapping);
        $resolverDefinition = $container->findDefinition($this->getResolverServiceID());

        foreach ($mapping as $name => $options) {
            $resolverDefinition->addMethodCall('addSolution', [$name, null, $options]);
        }
    }

    protected function checkRequirements($id, array $tag)
    {
        if (empty($tag['alias']) || !is_string($tag['alias'])) {
            throw new \InvalidArgumentException(
                sprintf('Service tagged "%s" must have valid "alias" argument.', $id)
            );
        }
    }

    abstract protected function getTagName();

    abstract protected function getResolverServiceID();

    abstract protected function getParameterName();
}
