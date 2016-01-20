<?php

namespace Overblog\GraphBundle\Resolver;


use Overblog\GraphBundle\Definition\FieldInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FieldResolver implements ResolverInterface
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
     * @return FieldInterface
     */
    public function resolve($type)
    {
        return $this->getTypeFromAlias($type);
    }

    private function getTypeServiceIdFromAlias($alias)
    {
        $typesMapping = $this->container->getParameter('overblog_graph.fields_mapping');

        if (!isset($typesMapping[$alias])) {
            throw new \RuntimeException(
                sprintf('Unknown field with alias "%s" (verified service tag)', $alias)
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
