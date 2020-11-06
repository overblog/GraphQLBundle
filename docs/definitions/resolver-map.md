Resolver map
============

In order to respond to queries, a schema needs to have resolve functions for all fields.
Resolve functions cannot be included in the GraphQL schema language, so they must be added separately.
This collection of functions is called the "resolver map".

Specification
--------------

A `resolverMap` is simple an object implementing `Overblog\GraphQLBundle\Resolver\ResolverMapInterface`
you can also just extend the concrete class `Overblog\GraphQLBundle\Resolver\ResolverMap`
and override `map` method and return an `array` or any `ArrayAccess` and `Traversable` implementation.

### Resolving from a resolverMap

* The `Overblog\GraphQLBundle\Resolver\ResolverMapInterface` exposes three methods: 
 `resolve`, `isResolvable` and `covered`.
 It also exposes constants representing some specials config fields.
* `resolve` takes two mandatory parameters: the type name and the config field name to resolve,
  which MUST be strings. `resolve` can return anything (a mixed value),
  or throw a `Overblog\GraphQLBundle\UnresolvableException` if the resolver for type name and config field name
  is not known to the resolverMap.
* `isResolvable` takes two parameters: the type name and the config field name to resolve,
  which MUST be strings.
  `isResolvable` MUST return true if the resolver for type name and config field name is known to
  the resolverMap and false if it is not. If `isResolvable($typeName, $fieldName)` returns false,
  `resolve($typeName, $fieldName)` MUST throw a `Overblog\GraphQLBundle\UnresolvableException`.
* `covered` takes unique optional parameter: the type name to resolve,
  which MUST be strings.
  `covered` MUST return an array of the names of the types covered if `$typeName`
  equal to null or return the type fields covered.
  If `covered($typeName)` returns an empty array or/and the fieldName is not present in array,
  `resolve($typeName, $fieldName)` MUST throw a `Overblog\GraphQLBundle\UnresolvableException`.
* constants (specials config fields):
  * [Union](type-system/union.md) and [Interface](type-system/interface.md) types
    - `Overblog\GraphQLBundle\Resolver\ResolverMapInterface::RESOLVE_TYPE` equivalent to `resolveType`.
  * [Object](type-system/object.md) type
    - `Overblog\GraphQLBundle\Resolver\ResolverMapInterface::RESOLVE_FIELD` equivalent to `resolveField`.
    - `Overblog\GraphQLBundle\Resolver\ResolverMapInterface::IS_TYPE_OF` equivalent to `isTypeOf`.
  * [Custom scalar](type-system/scalars.md#custom-scalar) type
    - Direct usage:
      - `Overblog\GraphQLBundle\Resolver\ResolverMapInterface::SERIALIZE` equivalent to `serialize`
      - `Overblog\GraphQLBundle\Resolver\ResolverMapInterface::PARSE_VALUE` equivalent to `parseValue`
      - `Overblog\GraphQLBundle\Resolver\ResolverMapInterface::PARSE_LITERAL` equivalent to `parseLiteral`
    - Reusing an existing scalar type
      - `Overblog\GraphQLBundle\Resolver\ResolverMapInterface::SCALAR_TYPE` equivalent to `scalarType`

Usage
-----

The following is an example of a valid resolverMap object
that can be be used for [GraphQL schema language](graphql-schema-language.md#usage) :

```php
<?php

namespace App\Resolver;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Resolver\ResolverMap;

class MyResolverMap extends ResolverMap
{
    protected function map()
    {
        return [
            'Query' => [
                self::RESOLVE_FIELD => function ($value, ArgumentInterface $args, \ArrayObject $context, ResolveInfo $info) {
                    if ('baz' === $info->fieldName) {
                        $id = (int) $args['id'];

                        return findBaz('baz', $id);
                    }

                    return null;
                },
                'bar' => [Bar::class, 'getBar'],
            ],
            'Foo' => [
                self::RESOLVE_TYPE => function ($value) {
                    return isset($value['user']) ? 'Bar' : null;
                },
            ],
            // enum internal values
            'User' => [
                'TATA' => 1,
                'TITI' => 2,
                'TOTO' => 3,
            ],
            // custom scalar
            'Baz' => [
                self::SERIALIZE => function ($value) {
                    return sprintf('%s Formatted Baz', $value);
                },
                self::PARSE_VALUE => function ($value) {
                    if (!is_string($value)) {
                        throw new Error(sprintf('Cannot represent following value as a valid Baz: %s.', Utils::printSafeJson($value)));
                    }

                    return str_replace(' Formatted Baz', '', $value);
                },
                self::PARSE_LITERAL => function ($valueNode) {
                    if (!$valueNode instanceof StringValueNode) {
                        throw new Error('Query error: Can only parse strings got: '.$valueNode->kind, [$valueNode]);
                    }

                    return str_replace(' Formatted Baz', '', $valueNode->value);
                },
            ],
            // or reuse an existing scalar (note: description and name will be override by decorator)
            //'Baz' => [self::SCALAR_TYPE => function () { return new FooScalarType(); }],
        ];
    }
}
```

Each resolver map must be tagged with the `overblog_graphql.resolver_map` tag
that defines at which priority it should run for the given schema. The priority
is an optional attribute and it has a default value of 0. The higher the number,
the earlier the resolver map is executed.

```yaml
# config/services.yaml
services:
    App\Resolver\MyResolverMap1:
        tags:
            - { name: overblog_graphql.resolver_map, schema: default }
    
    App\Resolver\MyResolverMap2:
        tags:
            - { name: overblog_graphql.resolver_map, schema: default, priority: 10 }
```

**Notes:**
- ResolverMap will override **all matching entries** when decorating types.
- ResolverMap does not supports `access`, `public` and `query complexity` right now.
- Many resolver map can be set for the same schema.
  In this case the first resolverMap in list where `isResolvable`
  returns `true` will be use.
- You don’t need to specify resolvers for every type in your schema.
  If you don’t specify a resolver, GraphQL falls back to a default one.

Credits
-------

This feature was inspired by [Apollo GraphQL tools](https://www.apollographql.com/docs/graphql-tools/resolvers.html).

Next step [solving N+1 problem](solving-n-plus-1-problem.md)
