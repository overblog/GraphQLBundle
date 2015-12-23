<?php

namespace Overblog\GraphBundle\Definition\Builder;

use GraphQL\Type\Definition\EnumType;
use Overblog\GraphBundle\Definition\TypeResolverInterface;

abstract class EnumDefinition extends TypeDefinition
{
    public function values()
    {
        return [];
    }

    final public function createType(TypeResolverInterface $resolver)
    {
        $values = $this->values();

        foreach ($values as $name => $value) {
            $values[$name] = ['value' => $value];
        }

        return new EnumType([
            'name' => $this->name(),
            'values' => $values,
        ]);
    }
}
