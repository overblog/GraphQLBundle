# Annotations reference

In the following reference examples the line `use Overblog\GraphQLBundle\Annotation as GQL;` will be omitted.

## Notes

-   When an annotation requires an expression, the `@=` will be added automatically if it's not set.

    -   For example, `@GQL\Access("isAuthenticated()")` will be converted to `['access' => '@=isAuthenticated()']` during the compilation.

-   You can use multiple type annotations on the same class. For example, if you need your class to be a GraphQL Type AND a Graphql Input, you just need to add the two annotations. Incompatible annotations or properties for a specified Type will simply be ignored.

In the following example, both the type `Coordinates` and the input type `CoordinatesInput` will be generated during the compilation process.  
As fields on input types don't support resolvers, the field `elevation` will simply be ignored to generate the input type (it will only have two fields: `latitude` and `longitude`).

```php
<?php

/**
 * @GQL\Type
 * @GQL\Input
 */
class Coordinates {
    /**
     * @GQL\Field(type="Float!")
     */
    public $latitude;

    /**
     * @GQL\Field(type="Float!")
     */
    public $longitude;

    /**
     * @GQL\Field(type="Float!", resolve="resolver('elevation_resolver', [value.latitude, value.longitude])")
     */
    public $elevation;
}
```

## Index

[@Access](#access)

[@Arg](#arg)

[@Deprecated](#deprecated)

[@Description](#description)

[@Enum](#enum)

[@EnumValue](#enumvalue)

[@Field](#field)

[@FieldsBuilder](#fieldsbuilder)

[@Input](#input)

[@IsPublic](#ispublic)

[@Mutation](#mutation)

[@Provider](#provider)

[@Query](#query)

[@Type](#type)

[@TypeInterface](#typeinterface)

[@Scalar](#scalar)

[@Union](#union)

[@Relay\Connection](#relayconnection)

[@Relay\Edge](#relayedge)

---

## @Access

Added on a _class_ in conjunction with `@Type` or `@TypeInterface`, this annotation will define the default access type on this fields.  
Added on a _property_ or _method_ in conjunction with `@Field`, it'll define the access type on this particular field.

Example:

```php
<?php

/**
 * @GQL\Type()
 * @GQL\Access("isAuthenticated()")
 */
class Hero {
    /**
     * @GQL\Field(type="String")
     */
    public $name;

    /**
     * @GQL\Field(type="String")
     * @GQL\Access("hasRole('ROLE_ADMIN')")
     */
    public $secret;
}
```

## @Arg

This annotation is used in the `args` attribute of a `@Field` or `@Query` or `@Mutation` to define an argument.

Required attributes:

-   **name** : The GraphQL name of the field argument (default to class name)
-   **type** : The GraphQL type of the field argument

Optional attributes:

-   **description** : The GraphQL description of the field argument

Example:

```php
<?php

/**
 * @GQL\Type
 */
class Hero {
    /**
     *  @GQL\Field(fieldBuilder={"GenericIdBuilder", {"name": "heroId"}})
     */
    public $id;

    /**
     * @GQL\Field(type="[Hero]",
     * args={
     *     @GQL\Arg(name="droidsOnly", type="Boolean", description="Retrieve only droids heroes"),
     *     @GQL\Arg(name="nameStartsWith", type="String", description="Retrieve only heroes with name starting with")
     * },
     * resolve="resolver('hero_friends', [args['droidsOnly'], args['nameStartsWith']])"
     * )
     */
    public $friends;
}
```

## @Deprecated

This annotation is used in conjunction with `@Field` to mark it as deprecated with the specified reason.

Example

```php
<?php

/**
 * @GQL\Type()
 */
class Hero {
    /**
     * @GQL\Field(type="String")
     */
    public $name;

    /**
     * @GQL\Field(type="Int")
     * @GQL\Deprecated("This field is deprecated in v2.0")
     */
    public $age;
}
```

## @Description

This annotation is used in conjunction with one of `@Enum`, `@Field`, `@Input`, `@Scalar`, `@Type`, `@TypeInterface`, `@Union` to set a description for the GraphQL object.

Example

```php
<?php

/**
 * @GQL\Type()
 * @GQL\Description("The Hero type represents a hero")
 */
class Hero {
    /**
     * @GQL\Field(type="String")
     * @GQL\Description("The name of the hero")
     */
    public $name;
}
```

## @Enum

This annotation applies on _class_ to define it as a `enum`. The constants defined on the class will be the enum values.  
In order to add more meta on the values (like description or deprecated reason), you have to provided them as `@EnumValue` in the `values` attribute with a `name` attribute referencing a constant name. You just need to do it for the constants you want to add meta on.

Optional attributes:

-   **name** : The GraphQL name of the enum (default to the class name without namespace)
-   **values** : An array of `@EnumValue`to define description or deprecated reason of enum values

The class will also be used by the `Arguments Transformer` service when an `Enum` is encoutered in a Mutation or Query Input. A property accessor will try to populate a property name `value`.

Example:

```php
<?php

/**
 * @GQL\Enum(values={
 *    @GQL\EnumValue(name="TATOUINE", description="The planet of Tatouine"),
 *    @GQL\EnumValue(name="BESPIN", deprecationReason="Not used anymore. The planet has been destroyed !")
 * })
 * @GQL\Description("The list of planets!")
 */
class Planet
{
    const DAGOBAH = 1;
    const TATOUINE = "2";
    const HOTH = "3";
    const BESPIN = "4";

    public $value;
}
```

In the example above, if a query or mutation has this Enum as an argument, the value will be an instance of the class with the enum value as the `value` property. (see [The Arguments Transformer documentation](arguments-transformer.md)).  
As the class can be instanciated from the `Arguments Transformer` service, it cannot have a constructor with required arguments.

## @EnumValue

This annotation is used in the `values` attribute on the `@Enum` annotation to add a description or deprecation reason on his value. See `@Enum` example above.

Required attributes:

-   **name** : The name of the targeted enum value

Optional attributes:

-   **description** : The GraphQL description of the enum value
-   **deprecationReason** : A deprecation reason for this enum value

## @Field

This annotation can be defined on a _property_ or a _method_.

If it is defined on a _method_:

-   If no `resolve` attribute is defined, it will default to `@=value.methodName(...args)"`, so the method itself will be used as the field resolver. You can then specify a `name` for this field (or the method's name will be use).

If it is defined on a _method_ of the Root Query or the Root mutation :

-   If not `resolve` attribute is defined, it will default to `@=service(FQN).methodName(...args)"` with `FQN` being the fully qualified name of the Root Query class or Root Mutation.

Optional attributes:

-   **type** : The GraphqL type of the field. This attribute can sometimes be guessed automatically from Doctrine ORM annotations
-   **name** : The GraphQL name of the field (default to the property name). If you don't specify a `resolve` attribute while changing the `name`, the default one will be '@=value.<property_name>'
-   **args** : An array of `@Arg`
-   **resolve** : A resolution expression
-   **fieldBuilder** : A field builder to use. Either as string (will be the field builder name), or as an array, first index will the name of the builder and second one will be the config.
-   **argsBuilder** : An args builder to use. Either as string (will be the args builder name), or as an array, first index will the name of the builder and second one will be the config.

Example on properties:

```php
<?php

/**
 * @GQL\Type()
 */
class Hero {
    /**
     *  @GQL\Field(fieldBuilder={"GenericIdBuilder", {"name": "heroId"}})
     */
    public $id;

    /**
     * @GQL\Field(
     *   type="[Hero]",
     *   argsBuilder="Pager"
     *   resolve="resolver('hero_friends', [value, args['page']])"
     * )
     */
    public $friends;
}
```

Example on methods:

```php
<?php

/**
 * @GQL\Type()
 */
class Hero {
    /**
     * @GQL\Field(
     *   name="friends",
     *   type="[Hero]",
     *   args={@GQL\Arg(name="limit", type="Int")}
     * )
     */
    public function getFriends(int $limit) {
        return array_slice($this->friends, 0, $limit);
    }
}
```

## @FieldsBuilder

This annotation is used on the attributes `builders` of a `@Type` annotation.
It is used to add fields builder to types (see [Fields builders](../definitions/builders/fields.md)))

Required attributes:

-   **builder** : The name of the fields builder

Optional attributes:

-   **builderConfig** : The configuration to pass to the fields builder

Example:

```php
<?php

/**
 * @GQL\Type(name="MyType", builders={@GQL\FieldsBuilder(builder="Timestamped")})
 */
class MyType {

}
```

## @Input

This annotation is used on a _class_ to define an input type.
An Input type is pretty much the same as an input, except:

-   Dynamic `@Field` with `resolve` attribute are ignored.

Optional attributes:

-   **name** : The GraphQL name of the input field (default to classnameInput )
-   **isRelay** : Set to true if you want your input to be relay compatible (ie. An extra field `clientMutationId` will be added to the input)

The corresponding class will also be used by the `Arguments Transformer` service. An instance of the corresponding class will be use as the `input` value if it is an argument of a query or mutation. (see [The Arguments Transformer documentation](arguments-transformer.md)).

## @IsPublic

Added on a _class_ in conjunction with `@Type` or `@TypeInterface`, this annotation will define the defaut to set if fields are public or not.
Added on a _property_ or _method_ in conjunction with `@Field`, it'll define an expression to set if the field is public or not.

Example:

```php
<?php

/**
 * @GQL\Type()
 * @GQL\IsPublic("isAuthenticated()")
 */
class SecretArea {
    /**
     * @GQL\Field(type="String")
     * @GQL\Field("hasRole('ROLE_ADMIN')")
     */
    public $secretCode;
}
```

## @Mutation

This annotation applies on methods for classes tagged with the `@Provider` annotation. It indicates that the method on this class will resolve a Mutation field.  
The resulting field is added to the root Mutation type (defined in configuration at key `overblog_graphql.definitions.schema.mutation`).  
The class exposing the mutation(s) must be declared as a [service](https://symfony.com/doc/current/service_container.html).

Example:

This will add an `updateUserEmail` mutation, with as resolver `@=service('App\Graphql\MutationProvider').updateUserEmail(...)`.

```php
<?php

namespace App\Graphql\Mutation;

/**
 * @GQL\Provider
 */
class MutationProvider {

    /**
     * @GQL\Mutation(type="User", args={
     *    @GQL\Arg(name="id", type="ID!"),
     *    @GQL\Arg(name="newEmail", type="String!")
     * })
     */
    public function updateUserEmail(string $id, string $newEmail) {
        $user = $this->repository->find($id);
        $user->setEmail($newEmail);
        $this->repository->save($user);

        return $user;
    }
}
```

## @Provider

This annotation applies on classes to indicate that it contains methods tagged with `@Query` or `@Mutation`.  
Without it, the `@Query` and `@Mutation` are ignored. When used, **remember to have a corresponding service with the fully qualified name of the class as service id**.  
You can use `@Access` and/or `@IsPublic` on a provider class to add default access or visibility on defined query or mutation.  

Optional attributes:

-   **prefix** : A prefix to apply to all field names from this provider

## @Query

This annotation applies on methods for classes tagged with the `@Provider` annotation. It indicates that on this class a method will resolve a Query field.  
By default, the resulting field is added to the root Query type (define in configuration at key `overblog_graphql.definitions.schema.query`).  
The class exposing the query(ies) must be declared as a [service](https://symfony.com/doc/current/service_container.html).

Optional attributes:

-   **targetType** : The GraphQL type to attach the field to (by default, it'll be the root Query type).

Example:

This will add a `users` property on the main query object, with a resolver `@=service('App\Graphql\Query\UsersProviders').getUsers()`.

```php
<?php

namespace App\Graphql\Query;

/**
 * @GQL\Provider
 */
class UsersProviders {

    /**
     * @GQL\Query(type="[User]", name="users")
     */
    public function getUsers() {
        return $this->repository->findAll();
    }
}
```

## @Type

This annotation is used on _class_ to define a GraphQL Type.

Optional attributes:

-   **name** : The GraphQL name of the type (default to the class name without namespace)
-   **interfaces** : An array of GraphQL interface this type inherits from
-   **isRelay** : Set to true to have a Relay compatible type (ie. A `clientMutationId` will be added).
-   **builders**: An array of `@FieldsBuilder` annotations

```php
<?php

/**
 * @GQL\Type(interfaces={"Character"}, builders={@GQL\FieldsBuilder(builder="Timestamped")})
 */
class Hero {
    /**
     * @GQL\Field(type="String")
     */
    public $name;
}
```

## @TypeInterface

This annotation is used on _class_ to define a GraphQL interface.

Required attributes:

-   **resolveType** : An expression to resolve the types

Optional attributes:

-   **name** : The GraphQL name of the interface (default to the class name without namespace)

## @Scalar

This annotation is used on a _class_ to define a custom scalar.

Optional attributes:

-   **name** : The GraphQL name of the interface (default to the class name without namespace)
-   **scalarType** : An expression to reuse an other scalar type

Example:

```php
<?php

use GraphQL\Language\AST\Node;

/**
 * @GQL\Scalar(name="DateTime")
 * @GQL\Description("Datetime scalar")
 */
class DateTimeType
{
    /**
     * @param \DateTimeInterface $value
     *
     * @return string
     */
    public static function serialize(\DateTimeInterface $value)
    {
        return $value->format('Y-m-d H:i:s');
    }

    /**
     * @param mixed $value
     *
     * @return \DateTimeInterface
     */
    public static function parseValue($value)
    {
        return new \DateTimeImmutable($value);
    }

    /**
     * @param Node $valueNode
     *
     * @return \DateTimeInterface
     */
    public static function parseLiteral(Node $valueNode)
    {
        return new \DateTimeImmutable($valueNode->value);
    }
}
```

## @Union

This annotation is used on a _class_ to define an union.

Required attributes:

-   **types** : An array of GraphQL Type as string

Optional attributes:

-   **name** : The GraphQL name of the union (default to the class name without namespace)
-   **resolveType** : Expression to resolve an object type. By default, it'll use a static method `resolveType` on the related class and call it with the `type resolver` as first argument and then the `value`.

Example:

```php
<?php

/**
 * @GQL\Union(types={"Cat", "Bird", "Snake"})
 * @GQL\Description("All the pets")
 */
class Pet {
    public static function resolveType(TypeResolver $typeResolver, $value)
    {
        if ($value->hasWings()) {
            return $typeResolver->resolve('Bird');
        } else if (!$value->hasArms()) {
            return $typeResolver->resolve('Snake');
        } else {
            return $typeResolver->resolve('Cat');
        }
    }
}
```


## @Relay\Connection

This annotation extends the `@Type` annotation so it uses the same attributes. 
It prepends the `RelayConnectionFieldsBuilder` to the list of fields builders.

The extra attributes are :

-   **edge** : The GraphQL type of the connection's edges
-   **node** : The GraphQL type of the node of the connection's edges' 

You must define one and only one of this attributes.  
If the `edge` attribute is used, the declaration is the same as adding a `RelayConnectionFieldsBuilder` 

```php
<?php

/**
 * @GQL\Relay\Connection(edge="MyConnectionEdge")
 */
class MyConnection {}

... is the same as ...

/**
 * @GQL\Type(builders={
 *      @GQL\FieldsBuilder(builder="relay-connection", builderConfig={edgeType="MyConnectionEdge"})
 * })
 */
class MyConnection {}
```

If the `node` attribute is used, a standard edge type will be automatically generated by suffixing the connection name with `Edge`.

```php
<?php

/**
 * @GQL\Relay\Connection(node="MyType")
 */
class MyConnection {}

... is the same as ...

/**
 * @GQL\Type(builders={
 *      @GQL\FieldsBuilder(builder="relay-edge", builderConfig={nodeType="MyType"})
 * })
 */
class MyConnectionEdge {}

/**
 * @GQL\Type(builders={
 *      @GQL\FieldsBuilder(builder="relay-connection", builderConfig={edgeType="MyConnectionEdge"})
 * })
 */
class MyConnection {}

```

## @Relay\Edge

This annotation extends the `@Type` annotation so it uses the same attributes.  
It prepends the `RelayEdgeFieldsBuilder` to the list of fields builders.

The extra attribute is :

-   **node** : The GraphQL type of the edge's node

```php
<?php

/**
 * @GQL\Relay\Edge(node="MyType")
 */
class MyEdge {}

... is the same as ...

/**
 * @GQL\Type(builders={
 *      @GQL\FieldsBuilder(builder="relay-edge", builderConfig={nodeType="MyType"})
 * })
 */
class MyEdge {}
```
