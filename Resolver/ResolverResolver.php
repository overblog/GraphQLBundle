<?php

namespace Overblog\GraphBundle\Resolver;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ResolverResolver implements ResolverInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $type
     * @return mixed
     */
    public function resolve($type)
    {
        $resolver = $this->getTypeFromAlias($type);
        if ($resolver instanceof ContainerAwareInterface) {
            $resolver->setContainer($this->container);
        }

        return $resolver;
    }

    private function getTypeServiceIdFromAlias($alias)
    {
        $typesMapping = $this->container->getParameter('overblog_graph.resolvers_mapping');

        if (!isset($typesMapping[$alias])) {
            throw new UnresolvableException(
                sprintf('Unknown resolver with alias "%s" (verified service tag)', $alias)
            );
        }

        return $typesMapping[$alias];
    }

    public function getTypeFromAlias($alias)
    {
        $serviceId = $this->getTypeServiceIdFromAlias($alias);

        return $serviceId !== null ? $this->container->get($serviceId) : null;
    }
}
