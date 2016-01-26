<?php

namespace Overblog\GraphBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use Overblog\GraphBundle\Resolver\ResolverInterface;

class TypeBuilder implements ConfigBuilderInterface
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

    public function getBaseClassName($type)
    {
        switch($type) {
            case 'connection':
                $class = 'Overblog\\GraphBundle\\Relay\\Connection\\ConnectionType';
                break;

            case 'object':
            case 'enum':
            case 'interface':
            case 'union':
            case 'inputObject':
                $class = sprintf('GraphQL\\Type\\Definition\\%sType', ucfirst($type));
                break;

            default:
                throw new \RuntimeException(sprintf('Type "%s" is not managed.'), $type);
        }

        return $class;
    }
}
