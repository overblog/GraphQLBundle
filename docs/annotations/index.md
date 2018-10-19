# Annotations

## Annotations reference
- [Annotations reference](annotations-reference.md)

## Annotations & type inheritance

As PHP classes naturally support inheritances (and so is the annotation reader), it doesn't make sense to allow classes to use the "inherits" option.  
The type will inherits the annotations declared on parent classes properties and methods. The annotation on the class itself will not be herited.

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
In the previous example, we need a service name `App\GraphQL\Query\RootQuery`. It workds the same way for mutations.
In the previous example, the generated `resolve` config of the `something` field will be `@=service('App\GraphQL\Query\RootQuery').getSomething()`.


## Type & Args auto-guessing

### @Field type auto-guessing from Doctrine ORM Annotations

Based on other Doctrine annotations on your fields, the corresponding GraphQL type can sometimes be guessed automatically.  

The type can be auto-guess from :

- `@ORM\Column` annotations, based on the `type` attribute
- `@ORM\ManyToOne`, `@ORM\ManyToMany`, `@ORM\OneToOne`, `@ORM\OneToMany`, based on the `targetEntity` attribute. The target entity must be a GraphQL type itself to work.
    - `@ORM\ManyToOne`, `@ORM\OneToOne`     The generated type will also use the `@ORM\JoinColumn` annotation and his `nullable` attribute to generate either `Type` or `Type!`
    - `@ORM\ManyToMany`, `@ORM\OneToMany`   The generated type will always be not null, like `[Type]!` as you're supposed to initialize corresponding properties with an ArrayCollection

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
