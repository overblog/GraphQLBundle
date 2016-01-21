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
        $class = $this->getClassBaseField($type);

        return new $class($this->configResolver->resolve($config));
    }

    public function getClassBaseField($field)
    {
        switch($field) {
            case 'mutation':
                $class = 'Overblog\\GraphBundle\\Relay\\Connection\\Mutation\\MutationField';
                break;

            default:
                throw new \RuntimeException(sprintf('Field "%s" is not managed.'), $field);
        }

        return $class;
    }
}
