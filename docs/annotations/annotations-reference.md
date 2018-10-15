# Annotations reference

In the following reference examples, the `use Overblog\GraphQLBundle\Annotation as GQL;` will is omitted.

## Notes

-   When a annotation require an expression, the `@=` will be added automatically if it is not set.

    -   For example, `@GQL\Access("isAuthenticated()")` will be converted to `['access' => '@=isAuthenticated()']`.

-   You can use multiple type annotations on the same class. For example, if you need your class to be a Graphql Type AND a Graphql InputType, you just need to add the two annotations. Incompatible annotations or properties for a specified Type will simply be ignored.

In the following example, both the type `Coordinates` and the input type `CoordinatesInput` will be generated.  
As fields on input type don't support resolvers, the field `elevation` will simply be ignored to generate the input type (it will only have two fields: `latitude` and `longitude`).

```php
<?php

/**
 * @GQL\Type
 * @GQL\InputType
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
>
```

## Index

@Access

@ArgsBuilder

@Deprecated

@Description

@Enum

@EnumValue

@Field

@FieldArg

@FieldBuilder

@InputType

@IsPublic

@Type

@TypeInterface

@Scalar

@Union

## @Access

Added on a _class_ in conjonction with `@Type` or `@TypeInterface`, this annotation will define the default access type on this fields.  
Added on a _property_ or _method_ in conjonction with `@Field`, it'll define the access type on this particular field.

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
     * @GQL\Field("hasRole('ROLE_ADMIN')")
     */
    public $secret;
}
?>
```

## @Deprecated

This annotation is used in conjonction with `@Field` to mark it as deprecated with the specified reason.

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
?>
```

## @Description

This annotation is used in conjonction with one of `@Enum`, `@Field`, `@InputType`, `@Scalar`, `@Type`, `@TypeInterface`, `@Union` to set a description for the GraphQL object.

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
?>
```

## @Enum

This annotation applies on _class_ to define it as a enum. The constants defined on the class will be the enum values.  
In order to add more meta on the values (like description or deprecated reason), you have to provided them as `@EnumValue` in the `values` attribute with a `name` attribute referencing a constant name. You just need to do it for the constants you want to add meta on.

Optional attributes:

-   **name** : The GraphQL name of the enum (default to the class name with suffix `Enum` if not already have)
-   **values** : An array of `@EnumValue`to define description or deprecated reason of enum values

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
}
?>
```

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

-   If no `resolve` attribute is define, it will default to `@=value_resolver(args, methodName)"`, so the method itself will be used as the field resolver. You can then specify a `name` for this field (or it's the method name that will be use).

Optional attributes:

-   **type** : The GraphqL type of the field (this attribute is required if no builder is define on the field)
-   **name** : The GraphQL name of the field (default to the property name). If you don't specify a `resolve` attribute while changing the `name`, the default one will be '@=value.<property_name>'
-   **args** : A array of `@FieldArg`
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
?>
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
     *   args={@GQL\FieldArg(name="limit", type="Int")}
     * )
     */
    public function getFriends(int $limit) {
        return array_slice($this->friends, 0, $limit);
    }
}
?>
```

## @FieldArg

This annotation is used in the `args` attribute of a `@Field` to define an argument.

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
     *     @GQL\FieldArg(name="droidsOnly", type="Boolean", description="Retrieve only droids heroes"),
     *     @GQL\FieldArg(name="nameStartsWith", type="String", description="Retrieve only heroes with name starting with")
     * },
     * resolve="resolver('hero_friends', [args['droidsOnly'], args['nameStartsWith']])"
     * )
     */
    public $friends;
}
?>
```

## @InputType

This annotation is used on a _class_ to define an input type.
An Input type is pretty much the same as an input except:

-   Dynamic `@Field` with `resolve` attribute are ignored.

Optional attributes:

-   **name** : The GraphQL name of the input field (default to classnameInput )
-   **isRelay** : Set to true if you want your input to be relay compatible (ie. An extra field `clientMutationId` will be added to the input)

## @IsPublic

Added on a _class_ in conjonction with `@Type` or `@TypeInterface`, this annotation will define the defaut to set if fields are public or not.
Added on a _property_ or _method_ in conjonction with `@Field`, it'll define an expression to set if the field is public or not.

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
?>
```

## @Type

This annotation is used on _class_ to define a GraphQL Type.

Optional attributes:

-   **name** : The GraphQL name of the type (default to the class name without namespace)
-   **interfaces** : An array of GraphQL interface this type herits from
-   **isRelay** : Set to true to have a Relay compatible type (ie. A `clientMutationId` will be added).

```php
<?php

/**
 * @GQL\Type(interfaces={"Character"})
 */
class Hero {
    /**
     * @GQL\Field(type="String")
     */
    public $name;
}
?>
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

Example:

```php
<?php

/**
 * @GQL\Union(types={"Dog", "Cat", "Bird", "Snake"})
 * @GQL\Description("All the pets")
 */
class Pet {}
```
