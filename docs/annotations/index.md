# Annotations

In order to use annotations, you need to configure the mapping:
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

This will load all annotated classes in `%kernel.project_dir%/src/GraphQL` into the schema.

## Using Annotations as your only Mapping

If you only use annotations as mappings you need to add an empty `RootQuery` type.
Your config should look like this:
```yaml
# config/packages/graphql.yaml
overblog_graphql:
  definitions:
    schema:
      query: RootQuery
    mappings:
      types:
        - type: annotation
          dir: "%kernel.project_dir%/src/GraphQL"
          suffix: ~
```
Your `RootQuery` class should look like this:
```php
namespace App\GraphQL\Query;

/**
 * @GQL\Type
 */
class RootQuery
{
}
```
If you use mutations, you need a `RootMutation` type as well.
## Annotations reference
- [Annotations reference](annotations-reference.md)

## Arguments transformation, populating & validation
- [Arguments Transformer](arguments-transformer.md)

## Annotations & type inheritance

As PHP classes naturally support inheritance (and so is the annotation reader), it doesn't make sense to allow classes to use the "inherits" option (as on types declared using YAML).  
The type will inherit annotations declared on parent class properties and methods. The annotation on the class itself will not be inherited.

## Annotations, value & default resolver

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

The following rules apply for `@Field`, `@Query` and `@Mutation` annotations to guess a resolver when no `resolver` attribute is defined:  

- If `@Field` is defined on a property :
    - If `@Field`'s attribute `name` is defined and is not equal to the property name 
        - `@=value.<property name>` for a regular type
        - `@=service(<FQCN>).<property name>` for root query or root mutation

    - If `@Field`'s attribute `name` is not defined or is not equal to the property name
        - The default GraphQL resolver will be use for a regular type (no `resolve` configuration will be define).
        - `@=service(<FQCN>).<name>` for root query or root mutation

- If `@Field` is defined on a method :  
    - `@=call(value.<method name>, args)` for a regular type 
    - `@=call(service(<FQCN>).<method name>, args)` for root query or mutation


## Annotations, Root Query & Root Mutation

If you define your root Query or root Mutation type as a class with annotations, it will allow you to define methods directly on the class itself to be exposed as GraphQL fields.  
For example: 

```php
namespace App\GraphQL\Query;

/**
 * @GQL\Type
 */
class RootQuery {
    /**
     * @GQL\Field(name="something", type="String!")
     */
    public function getSomething() {
        return "Hello world!";
    }
}
```

In order for this to work, the `RootQuery` class must be instanciated at some point if you want to be able to call methods on it. 
To do so, the `RootQuery` class must be defined as a service with its FQCN as id.  
In the previous example, we need a service name `App\GraphQL\Query\RootQuery`. It works the same way for mutations.
In the previous example, the generated `resolve` config of the `something` field will be `@=service('App\GraphQL\Query\RootQuery').getSomething()`.


## Type & Args auto-guessing

### @Field type auto-guessing from Doctrine ORM Annotations

Based on other Doctrine annotations on your fields, the corresponding GraphQL type can sometimes be guessed automatically.  

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


### @Field type auto-guessing when defined on a method with a return type hint

The type of the `@Field` annotation can be auto-guessed if it's defined on a method with a return type hint.

For example:

```php
/**
 * @GQL\Type
 */
class MyType {
    /**
     * @GQL\Field
     */
    public function getSomething(): string {
        return "Hello world!";
    }
}
```

In this example, the type `String!` will be auto-guessed from the return type hint of the method.  

### @Field arguments auto-guessing when defined on a method with type hinted parameters

The arguments of the `@Field` annotation can be auto-guessed if it's defined on a method with type hinted arguments. Arguments without default value will be consided required.

For example:

```php
/**
 * @GQL\Type
 */
class MyType {
    /**
     * @GQL\Field(type="[String]!")
     */
    public function getSomething(int $amount, string $name, MyInputClass $input, int $limit = 10) {
        ...
    }
}
```

The GraphQL arguments will be auto-guessed as:  

- `@Arg(name="amount", type="Int!")`
- `@Arg(name="name", type="String!")`
- `@Arg(name="input", type="MyInput!")`  (The input type corresponding to the `MyInputClass` will be used).
- `@Arg(name="limit", type="Int", default = 10)`

### Limitation of auto-guessing:

When trying to auto-guess a type or args based on PHP Reflection (from type hinted method parameters or type hinted return value), there is a limitation.  
As PHP type hinting doesn't support "array of instances of class", we cannot rely on it to guess the type when dealing with collection of objects.  
In these case, you'll need to declare your types or arguments type manually.

For example, in PHP, a signature like this : `public function getArrayOfStrings(): string[] {}` is invalid. 







