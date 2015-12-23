<?php

namespace Overblog\GraphBundle;

use GraphQL\Schema;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;

function schema($query, $mutation = null)
{
    return new Schema($query, $mutation);
}

function lazy(&$type)
{
    return function () use (&$type) {
        return $type;
    };
}

function booleanType()
{
    return Type::boolean();
}

function idType()
{
    return Type::id();
}

function intType()
{
    return Type::int();
}

function floatType()
{
    return Type::float();
}

function stringType()
{
    return Type::string();
}

function listOf($type)
{
    return new ListOfType($type);
}

function nonNull($type)
{
    return new NonNull($type);
}

function enumType($name, array $values)
{
    foreach ($values as $key => $value) {
        $values[$key] = ['value' => $value];
    }

    return new EnumType([
        'name' => $name,
        'values' => $values,
    ]);
}

function inputObjectType($name, array $fields)
{
    return new InputObjectType([
        'name' => $name,
        'fields' => __toAssoc($fields),
    ]);
}

function interfaceType($name, array $fields, $resolve = null)
{
    return new InterfaceType([
        'name' => $name,
        'fields' => __toAssoc($fields),
        'resolveType' => $resolve,
    ]);
}

function objectType($name, array $interfaces, $fields = null)
{
    if (empty($fields)) {
        $fields = $interfaces;
        $interfaces = null;
    }

    return new ObjectType([
        'name' => $name,
        'interfaces' => $interfaces,
        'fields' => __toAssoc($fields),
    ]);
}

function unionType($name, array $types, $resolve = null)
{
    return new UnionType([
        'name' => $name,
        'types' => $types,
        'resolveType' => $resolve,
    ]);
}

function field($name, $type, $resolve = null, array $arguments = null)
{
    return [$name, [
        'type' => $type,
        'args' => __toAssoc($arguments),
        'resolve' => $resolve,
    ]];
}

function argument($name, $type, $defaultValue = null)
{
    return [$name, [
        'type' => $type,
        'defaultValue' => $defaultValue,
    ]];
}

function __toAssoc(array $entries = null)
{
    if (empty($entries)) {
        return [];
    }

    $map = [];

    foreach ($entries as list($key, $value)) {
        $map[$key] = $value;
    }

    return $map;
}
