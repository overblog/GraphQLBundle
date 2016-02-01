<?php

namespace Overblog\GraphBundle\Resolver;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ResolverResolver extends AbstractResolver
{
    /**
     * @param $alias
     * @return mixed
     */
    public function resolve($alias)
    {
        if (null !== $resolver = $this->cache->fetch($alias)) {
            return $resolver;
        }
        $resolver = $this->getResolverFromAlias($alias);
        if ($resolver instanceof ContainerAwareInterface) {
            $resolver->setContainer($this->container);
        }

        $this->cache->save($alias, $resolver);

        return $resolver;
    }

    private function getResolverServiceIdFromAlias($alias)
    {
        $typesMapping = $this->container->getParameter('overblog_graph.resolvers_mapping');

        if (!isset($typesMapping[$alias])) {
            throw new UnresolvableException(
                sprintf('Unknown resolver with alias "%s" (verified service tag)', $alias)
            );
        }

        return $typesMapping[$alias];
    }

    private function getResolverFromAlias($alias)
    {
        $serviceId = $this->getResolverServiceIdFromAlias($alias);

        return $serviceId !== null ? $this->container->get($serviceId) : null;
    }
}
