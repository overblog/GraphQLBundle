<?php

namespace Overblog\GraphBundle\Definition\Builder;

use Overblog\GraphBundle\Definition\TypeResolverInterface;

abstract class TypeDefinition
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function name()
    {
        return $this->name;
    }

    abstract public function createType(TypeResolverInterface $resolver);
}
