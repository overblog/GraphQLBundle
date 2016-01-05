<?php

namespace Overblog\GraphBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use Overblog\GraphBundle\Resolver\ResolverInterface;

class TypeBuilder
{
    private $configResolver;

    public function __construct(ResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    /**
     * @param $definition
     * @return Type
     */
    public function create($definition)
    {
        $type = $definition['type'];
        $config = $definition['config'];
        $class = $this->getClassBaseType($type);

        return new $class($this->configResolver->resolve($config));
    }

    public function getClassBaseType($type)
    {
        $class = sprintf('GraphQL\\Type\\Definition\\%sType', ucfirst($type));

        return $class;
    }
}
