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
        $class = $this->getBaseClassName($type);

        return new $class($this->configResolver->resolve($config));
    }

    private function getBaseClassName($type)
    {
        switch($type) {
            case 'connection':
                $class = 'Overblog\\GraphBundle\\Relay\\Connection\\ConnectionType';
                break;

            case 'node':
                $class = 'Overblog\\GraphBundle\\Relay\\Node\\NodeInterfaceType';
                break;

            case 'input':
                $class = 'Overblog\\GraphBundle\\Relay\\Mutation\\InputType';
                break;

            case 'payload':
                $class = 'Overblog\\GraphBundle\\Relay\\Mutation\\PayloadType';
                break;

            case 'object':
            case 'enum':
            case 'interface':
            case 'union':
                $class = sprintf('GraphQL\\Type\\Definition\\%sType', ucfirst($type));
                break;

            default:
                throw new \RuntimeException(sprintf('Type "%s" is not managed.'), $type);
        }

        return $class;
    }
}
