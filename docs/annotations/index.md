# Annotations

## Annotations reference
- [Annotations reference](annotations-reference.md)

## Annotations & type inheritance

As PHP classes naturally support inheritances (and so is the annotation reader), it doesn't make sense to allow classes to use the "inherits" option.  
The type will inherits the annotations declared on parent classes properties and methods. The annotation on the class itself will not be herited.

## @Field type auto-guessing from Doctrine ORM Annotations

Based on other Doctrine annotations on your fields, the corresponding GraphQL type can sometimes be guessed automatically.  

The type can be auto-guess from :

- `@ORM\Column` annotations, based on the `type` attribute
- `@ORM\ManyToOne`, `@ORM\ManyToMany`, `@ORM\OneToOne`, `@ORM\OneToMany`, based on the `targetEntity` attribute. The target entity must be a GraphQL type itself to work.
    - `@ORM\ManyToOne`, `@ORM\OneToOne`     The generated type will also use the `@ORM\JoinColumn` annotation and his `nullable` attribute to generate either `Type` or `Type!`
    - `@ORM\ManyToMany`, `@ORM\OneToMany`   The generated type will always be not null, like `[Type]!` as you're supposed to initialize corresponding properties with an ArrayCollection