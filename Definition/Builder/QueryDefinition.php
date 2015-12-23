<?php

namespace Overblog\GraphBundle\Definition\Builder;

use Overblog\GraphBundle\Definition\TypeResolverInterface;

abstract class QueryDefinition
{
    abstract public function type();

    abstract public function resolve($root, array $args);

    public function args()
    {
        return [];
    }

    public function toArray(TypeResolverInterface $resolver)
    {
        $type = $resolver->resolveType($this->type());
        $args = $this->args();

        foreach ($args as &$arg) {
            $arg['type'] = $resolver->resolveType($arg['type']);
        }

        return [
            'type' => $type,
            'args' => $args,
            'resolve' => [$this, 'resolve'],
        ];
    }
}
