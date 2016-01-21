<?php

namespace Overblog\GraphBundle\Resolver;


use GraphQL\Type\Definition\Type;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TypeResolver implements ResolverInterface
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
     * @param string $type
     * @return \GraphQL\Type\Definition\Type
     */
    public function resolve($type)
    {
        if (!is_string($type)) {
            return $type;
        }
        // Non-Null
        if ('!' === $type[strlen($type) - 1]) {
            return Type::nonNull($this->resolve(substr($type, 0, -1)));
        }
        // List
        if ('[' === $type[0]) {
            if (']' !== $type[strlen($type) - 1]) {
                throw new UnresolvableException(sprintf('Invalid type "%s"', $type));
            }
            return Type::listOf($this->resolve(substr($type, 1, -1)));
        }
        // Named
        return $this->getTypeFromAlias($type);
    }

    private function getTypeServiceIdFromAlias($alias)
    {
        $alias = str_replace(['[', ']', '!'], '', $alias);

        $typesMapping = $this->container->getParameter('overblog_graph.types_mapping');

        if (!isset($typesMapping[$alias])) {
            throw new UnresolvableException(
                sprintf('Unknown type with alias "%s" (verified service tag)', $alias)
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
