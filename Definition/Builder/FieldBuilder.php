<?php

namespace Overblog\GraphBundle\Definition\Builder;

use GraphQL\Type\Definition\Type;
use Overblog\GraphBundle\Resolver\ResolverInterface;

class FieldBuilder
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
            case 'mutation':
                $class = sprintf('Overblog\\GraphBundle\\Definition\\Relay\\%sField', ucfirst($type));
                break;

            default:
                throw new \RuntimeException(sprintf('Type "%s" is not managed.'), $type);
        }

        return $class;
    }
}
