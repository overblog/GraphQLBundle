# Annotations

## Annotations reference
- [Annotations reference](annotations-reference.md)

## Arguments transformation, populating & validation
- [Arguments Transformer](arguments-transformer.md)

## Annotations & type inheritance

As PHP classes naturally support inheritances (and so is the annotation reader), it doesn't make sense to allow classes to use the "inherits" option.  
The type will inherits the annotations declared on parent classes properties and methods. The annotation on the class itself will not be herited.


## Annotations, value & default resolver

In GraphQL, when a type's field is resolved, GraphQL expect by default a property (for object) or an index (for array) on the corresponding value returned for the type.  

For example, if I have a type like that :  

```graphql
type Character {
    name: String!
}
```

If the field `name` is queried, on a `Character` type instance, the default resolver will try to find a property or index on the related object (the `value`).
So, the value could be an object instance with a `name` property or an array with a `name` index.  

Except for the root query and the root mutation, the value object is always returned by an other resolver.  
For the Root query and the root mutation, the value object is the service with an id equals to the fully qualified name of the query/mutation class.  

On a `@Field` or `@Query` or `@Mutation`, the following rules apply to guess a resolver when no `resolver` attribute is define on the annotation:  

- If the  `@Field` apply on a property :
    - If the `@Field` attribute `name` is define and not equals to the property name 
        - `@=value.[property name]` for a regular type
        - `@=service([FQN class]).[property name]` for root query or root mutation

    - If the `@Field` attribute `name` is not define or equals the property
        - The default GraphQL resolver will be use for a regular type (no `resolve` configuration will be define).
        - `@=service([FQN class]).[name]` for root query or root mutation

- If the `@Field` apply on a method :  
    - `@=call(value.[method name], args)` for a regular type 
    - `@=call(service([FQN class]).[method name], args)` for root query or mutation


## Annotations, Root Query & Root Mutation

If you define your Root Query, or Root Mutation as a class with annotations, it will allow you to define methods directly on the class itself to be expose as GraphQL.  
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

In order for this to work, the Root Query class must be instanciated at some point if we want to be able to call methods on it. 
To do so, the RootQuery class must be define as a service with the class fully qualified name as id.  
In the previous example, we need a service name `App\GraphQL\Query\RootQuery`. It works the same way for mutations.
In the previous example, the generated `resolve` config of the `something` field will be `@=service('App\GraphQL\Query\RootQuery').getSomething()`.


## Type & Args auto-guessing

### @Field type auto-guessing from Doctrine ORM Annotations

Based on other Doctrine annotations on your fields, the corresponding GraphQL type can sometimes be guessed automatically.  

The type can be auto-guess from :

- `@ORM\Column` annotations, based on the `type` attribute
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


### @Field type auto-guessing when applied on a method with a return type hint

The type of a `@Field` annotation can be auto-guessed if it applies on a method with a return type hint.

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

### @Field arguments auto-guessing when applied on a method with type hinted parameters

The arguments of a `@Field` annotation can be auto-guessed if it applies on a method with type hinted arguments. Arguments without default value will be consided required.

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

When trying to auto-guess type or args based on PHP Reflection (from type hinted method parameters or type hinted return value), there is a limitation.  
As PHP type hinting doesn't support "array of instances of class", we cannot relay on it to guess the type when dealing with collection of objects.  
In these case, you'll need to declare your types or arguments type manually.

For example, in PHP, a signature like this : `public function getArrayOfStrings(): string[] {}` is invalid. 








