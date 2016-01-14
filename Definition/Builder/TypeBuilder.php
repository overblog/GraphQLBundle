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
     * @param $type
     * @param array $config
     * @return Type
     */
    public function create($type, array $config)
    {
        $class = $this->getClassBaseType($type);

        return new $class($this->configResolver->resolve($config));
    }

    public function getClassBaseType($type)
    {
        switch($type) {
            case 'connection':
                $class = 'Overblog\\GraphBundle\\Definition\\Relay\\ConnectionType';
                break;

            case 'object':
            case 'enum':
            case 'interface':
            case 'union':
            case 'inputObject':
            default:
                $class = sprintf('GraphQL\\Type\\Definition\\%sType', ucfirst($type));
                break;
        }

        return $class;
    }
}
