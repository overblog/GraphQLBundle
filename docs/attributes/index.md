# Attributes (PHP >= 8) & Annotations

In order to use attributes or annotations, you need to configure the mapping:

To use attributes, use the `attribute` mapping type.

```yaml
# config/packages/graphql.yaml
overblog_graphql:
  definitions:
    mappings:
      types:
        - type: attribute
          dir: "%kernel.project_dir%/src/GraphQL"
          suffix: ~
```

To use annotations, You must install `symfony/cache` and `doctrine/annotation` and use the `annotation` mapping type.

```yaml
# config/packages/graphql.yaml
overblog_graphql:
  definitions:
    mappings:
      types:
        - type: annotation
          dir: "%kernel.project_dir%/src/GraphQL"
          suffix: ~
```

Note: The annotation are deprecated as of version `1.3` and will be removed in the next major version.  

This will load all annotated classes in `%kernel.project_dir%/src/GraphQL` into the schema.

The annotations & attributes are equivalent and are used in the same way. They share the same annotation namespaces, classes and API.  

Example with attributes:
```php
use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Type]
class MyType {
    #[GQL\Field(type: "Int")]
    protected $myField;
}
```

Example with annotations:
```php
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 */
class MyType {
    /**
     * @GQL\Field(type="Int")
     */
    protected $myField;
}
```


## Using Attributes as your only Mapping

If you only use attributes as mappings you need to add an empty `RootQuery` type.  
Your config should look like this:  

```yaml
# config/packages/graphql.yaml
overblog_graphql:
  definitions:
    schema:
      query: RootQuery
    mappings:
      types:
        - type: attribute
          dir: "%kernel.project_dir%/src/GraphQL"
          suffix: ~
```
Your `RootQuery` class should look like this:  

```php
namespace App\GraphQL\Query;

#[GQL\Type]
class RootQuery
{
}
```
If you use mutations, you need a `RootMutation` type as well.


## Attributes reference
- [Attributes reference](attributes-reference.md)

## Arguments transformation, populating & validation
- [Arguments Transformer](arguments-transformer.md)

## Attributes & type inheritance

As PHP classes naturally support inheritance (and so is the attribute reader), it doesn't make sense to allow classes to use the "inherits" option (as on types declared using YAML).  
The type will inherit attributes declared on parent class properties and methods. The attribute on the class itself will not be inherited.

## Attributes, value & default resolver

In GraphQL, when a type's field is resolved, GraphQL expects by default a property (for object) or a key (for array) on the corresponding value returned for the type.  

For example, if you have a type like that :  

```graphql
type Character {
    name: String!
}
```

If the field `name` is queried, on a `Character` type instance, the default resolver will try to find a property or key on the related variable (the `value`).
So, the `value` could be an object instance with a `name` property or an array with a `name` key.  

Except for the root Query and root Mutation types, the `value` variable is always returned by another resolver.  
For the root Query and the Root Mutation types, the `value` variable is the service with an id that equals to the fully qualified name of the query/mutation class.  

The following rules apply for `#[GQL\Field]`, `#[GQL\Query]` and `#[GQL\Mutation]` attributes to guess a resolver when no `resolver` attribute is defined:  

- If `#[GQL\Field]` is defined on a property :
    - If `#[GQL\Field]`'s attribute `name` is defined and is not equal to the property name 
        - `@=value.<property name>` for a regular type
        - `@=service(<FQCN>).<property name>` for root query or root mutation

    - If `#[GQL\Field]`'s attribute `name` is not defined or is not equal to the property name
        - The default GraphQL resolver will be use for a regular type (no `resolve` configuration will be define).
        - `@=service(<FQCN>).<name>` for root query or root mutation

- If `#[GQL\Field]` is defined on a method :  
    - `@=call(value.<method name>, args)` for a regular type 
    - `@=call(service(<FQCN>).<method name>, args)` for root query or mutation


## Attributes, Root Query & Root Mutation

If you define your root Query or root Mutation type as a class with attributes, it will allow you to define methods directly on the class itself to be exposed as GraphQL fields.  
For example: 

```php
namespace App\GraphQL\Query;

#[GQL\Type]
class RootQuery {
    #[GQL\Field(name: "something", type: "String!")]
    public function getSomething() {
        return "Hello world!";
    }
}
```

In order for this to work, the `RootQuery` class must be instantiated at some point if you want to be able to call methods on it. 
To do so, the `RootQuery` class must be defined as a service with its FQCN as id.  
In the previous example, we need a service name `App\GraphQL\Query\RootQuery`. It works the same way for mutations.
In the previous example, the generated `resolve` config of the `something` field will be `@=service('App\GraphQL\Query\RootQuery').getSomething()`.


## Type & Args auto-guessing

If the `type` option is not defined explicitly on the `@Field`, `@Query` or `@Mutation`, the bundle will try to guess it from other DocBlock annotations or from the PHP type-hint, in the following order:

1. `@var` and `@return` annotations
2. type-hint 
3. Doctrine annotations

It will stop on the first successful guess.

### `#[GQL\Field]` type auto-guessing from DockBlock

The `type` option of the `#[GQL\Field]` attribute/annotation can be guessed if its DocBlock describes a known type. It is a more precise guessing as it supports collections of objects, e.g. `User[]` or `array<User>`.

For example:

```php
#[GQL\Type]
class MyType {
    /**
     * @var Friend[]
     */
    #[GQL\Field]
    public array $friends = [];
}
```


### `#[GQL\Field]` type auto-guessing when defined on a property with a type hint

The type of the `#[GQL\Field]` attribute/annotation can be auto-guessed if it's defined on a property with a type hint.
If the property has a usable type hint this is used and no further guessing is done.

For example:

```php
#[GQL\Type]
class MyType {
    #[GQL\Field]
    protected string $property;
}
```

In this example, the type `String!` will be auto-guessed from the type hint of the property.  

### `#[GQL\Field]` type auto-guessing from Doctrine ORM Annotations

Based on other Doctrine annotations on your fields, the corresponding GraphQL type can sometimes be guessed automatically.  
In order to activate this guesser, you must install `doctrine/orm` package.  

The type can be auto-guessed from the following annotations:

- `@ORM\Column`, based on the `type` attribute
- `@ORM\ManyToOne`, `@ORM\ManyToMany`, `@ORM\OneToOne`, `@ORM\OneToMany`, based on the `targetEntity` attribute. The target entity must be a GraphQL type itself to work.
    - `@ORM\ManyToOne`, `@ORM\OneToOne`     The generated type will also use the `@ORM\JoinColumn` annotation and his `nullable` attribute to generate either `Type` or `Type!`
    - `@ORM\ManyToMany`, `@ORM\OneToMany`   The generated type will always be not null, like `[Type]!` as you're supposed to initialize corresponding properties with an ArrayCollection

You can also provide your own doctrine / GraphQL types mappings in the bundle configuration.  
For example:

```yaml (graphql.yaml)
overblog_graphql:
    ...
    doctrine:
        types_mapping:
            text[]:    "[String]"
            datetime:  DateTime    # If you have registered this custom scalar

```


### #[GQL\Field] type auto-guessing when defined on a method with a return type hint

The type of the `#[GQL\Field]` annotation can be auto-guessed if it's defined on a method with a return type hint.

For example:

```php
#[GQL\Type]
class MyType {
    #[GQL\Field]
    public function getSomething(): string {
        return "Hello world!";
    }
}
```

In this example, the type `String!` will be auto-guessed from the return type hint of the method.  

### `#[GQL\Field]` arguments auto-guessing when defined on a method with type hinted parameters

The arguments of the `#[GQL\Field]` attribute can be auto-guessed if it's defined on a method with type hinted arguments. Arguments without default value will be consided required.

For example:

```php
#[GQL\Type]
class MyType {
    #[GQL\Field(type: "[String]!")]
    public function getSomething(int $amount, string $name, MyInputClass $input, int $limit = 10) {
        ...
    }
}
```

The GraphQL arguments will be auto-guessed as:  

- `#[GQL\Arg(name: "amount", type: "Int!")`
- `#[GQL\Arg(name: "name", type: "String!")`
- `#[GQL\Arg(name: "input", type: "MyInput!")`  (The input type corresponding to the `MyInputClass` will be used).
- `#[GQL\Arg(name: "limit", type: "Int", default: 10)`

It is possible to mix auto-guessing and manual declaration of arguments. Explicit declaration with `#[GQL\Arg]` will always take precedence over auto-guessing.  
If both are used, __it is important to name the arguments and the parameters the same way__, otherwise, they'll be considered as two different arguments.  
For example:  
```php
#[GQL\Type]
class MyType {
    #[GQL\Field]
    #[GQL\Arg(name: "totalAmount", type: "Int!")]
    public function doSomething(int $amount) {
        ...
    }
}
```
In this example, the `doSomething` field will have two arguments: `totalAmount` and `amount` and the system won't be able to pass the `totalAmount` argument to the `doSomething` method.  


### Limitation of auto-guessing:

When trying to auto-guess a type or args based on PHP Reflection (from type hinted method parameters or type hinted return value), there is a limitation.  
As PHP type hinting doesn't support "array of instances of class", we cannot rely on it to guess the type when dealing with collection of objects.  
In these case, you'll need to declare your types or arguments type manually.

For example, in PHP, a signature like this : `public function getArrayOfStrings(): string[] {}` is invalid. 







