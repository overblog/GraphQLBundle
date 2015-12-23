<?php

namespace Overblog\GraphBundle\Definition\Builder;

use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphBundle\Definition\TypeResolverInterface;

abstract class ObjectDefinition extends TypeDefinition
{
    public function fields()
    {
        return [];
    }

    public function interfaces()
    {
        return [];
    }

    final public function createType(TypeResolverInterface $resolver)
    {
        $fields = $this->fields();
        $interfaces = $this->interfaces();

        $resolveFields = function () use ($fields, $resolver) {
            foreach ($fields as &$field) {
                $field['type'] = $resolver->resolveType($field['type']);
            }

            return $fields;
        };

        $resolveInterfaces = function () use ($interfaces, $resolver) {
            foreach ($interfaces as $i => $type) {
                $interfaces[$i] = $resolver->resolveType($type);
            }

            return $interfaces;
        };

        return new ObjectType([
            'name' => $this->name(),
            'fields' => empty($fields) ? null : $resolveFields,
            'interfaces' => empty($interfaces) ? null : $resolveInterfaces,
        ]);
    }
}
