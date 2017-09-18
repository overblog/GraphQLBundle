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

use GraphQL\Type\Definition\Type;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

abstract class TaggedServiceMappingPass implements CompilerPassInterface
{
    private function getTaggedServiceMapping(ContainerBuilder $container, $tagName)
    {
        $serviceMapping = [];

        $taggedServices = $container->findTaggedServiceIds($tagName);

        foreach ($taggedServices as $id => $tags) {
            $className = $container->findDefinition($id)->getClass();
            $isType = is_subclass_of($className, Type::class);
            foreach ($tags as $tag) {
                $this->checkRequirements($id, $tag);
                $tag = array_merge($tag, ['id' => $id]);
                if (!$isType) {
                    $tag['method'] = isset($tag['method']) ? $tag['method'] : '__invoke';
                }
                if (isset($tag['alias'])) {
                    $serviceMapping[$tag['alias']] = $tag;
                }

                // add FQCN alias
                $alias = $className;
                if (!$isType && '__invoke' !== $tag['method']) {
                    $alias .= '::'.$tag['method'];
                }
                $tag['alias'] = $alias;
                $serviceMapping[$tag['alias']] = $tag;
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
            $cleanOptions = $options;
            $solutionID = $options['id'];

            $solutionDefinition = $container->findDefinition($solutionID);
            // make solution service public to improve lazy loading
            $solutionDefinition->setPublic(true);

            $methods = array_map(
                function ($methodCall) {
                    return $methodCall[0];
                },
                $solutionDefinition->getMethodCalls()
            );
            if (
                is_subclass_of($solutionDefinition->getClass(), ContainerAwareInterface::class)
                && !in_array('setContainer', $methods)
            ) {
                @trigger_error(
                    'Autowire custom tagged (type, resolver or mutation) services is deprecated as of 0.9 and will be removed in 1.0. Use AutoMapping or set it manually instead.',
                    E_USER_DEPRECATED
                );
                $solutionDefinition->addMethodCall('setContainer', [new Reference('service_container')]);
            }

            $resolverDefinition->addMethodCall(
                'addSolution',
                [$name, [new Reference('service_container'), 'get'], [$solutionID],  $cleanOptions]
            );
        }
    }

    protected function checkRequirements($id, array $tag)
    {
        if (isset($tag['alias']) && !is_string($tag['alias'])) {
            throw new \InvalidArgumentException(
                sprintf('Service tagged "%s" must have valid "alias" argument.', $id)
            );
        }
    }

    abstract protected function getTagName();

    abstract protected function getResolverServiceID();

    abstract protected function getParameterName();
}
