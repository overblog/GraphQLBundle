Inheritance
===========

In some cases, inheritance can help to not repeating yourself, better organisation,
clearer schema, easier interfaces implementation...

Imagine you have something like that:

![](_resources/type-inheritance/class-diagram.png)

This is how we will implement it:

```yaml
# First, let's define our mother class
Character:
    type: object
    heirs: # the opposite of « inherits »
           # optional if « inherits » already exists on daughters classes
      - CharacterWarrior
      - CharacterWizard
    config:
        fields:
            id: {type: Int!}
            type: {type: String!}
            name: {type: String!}
            staminaPoints: {type: Int!}

# Then let's define our daughters classes
CharacterWarrior:
    type: object
    inherits: [Character] # the opposite of « heirs » 
                          # optional if « heirs » already exists on mother class
    config:
        fields:
            furyPoints: {type: Int!}

CharacterWizard:
    type: object
    inherits: [Character] # the opposite of « heirs » 
                          # optional if « heirs » already exists on mother class
    config:
        fields:
            magicPoints: {type: Int!}
```

This is equivalent to:

```yaml
CharacterWarrior:
    type: object
    config:
        fields:
            id: {type: Int!}
            type: {type: String!}
            name: {type: String!}
            staminaPoints: {type: Int!}
            furyPoints: {type: Int!}

CharacterWizard:
    type: object
    config:
        fields:
            id: {type: Int!}
            type: {type: String!}
            name: {type: String!}
            staminaPoints: {type: Int!}
            magicPoints: {type: Int!}
```

**« So, we defined our types, but how can I query `furyPoints` field for `CharacterWarrior` only? »**

To do that, you should use [Inline Fragments](http://graphql.org/learn/queries/#inline-fragments), e.g.:

```graphql
{
    characters {
        id
        type
        name
        staminaPoints
        
        ... on CharacterWarrior {
            furyPoints
        }
        
        ... on CharacterWizard {
            magicPoints
        }
    }
}
```

**« But it does not work??? »**

Yes, we should refactor our types:
- `Character` will be an interface
- `CharacterWarrior` and `CharacterWizard` will **implement** the interface `Character`
- `CharacterWarrior` and `CharacterWizard` will **extend configuration fields** of interface `Character`

```yaml
Character:
    type: interface
    config:
        # depending of `value.type`, this resolver should 
        # returns `CharacterWarrior` or `CharacterWizard`
        resolveType: "@=resolver('character_type_resolver', [value.type])"
        fields:
            id: {type: Int!}
            type: {type: String!}
            name: {type: String!}
            staminaPoints: {type: Int!}

CharacterWarrior:
    type: object
    inherits: [Character] # We don't have to implement all `Character` fields
    config:
        interfaces: [Character] # `CharacterWarrior` implements `Character` interface
        fields:
            furyPoints: {type: Int!}

CharacterWizard:
    type: object
    inherits: [Character] # We don't have to implement all `Character` fields
    config:
        interfaces: [Character] # `CharacterWizard` implements `Character` interface 
        fields:
            magicPoints: {type: Int!}
```

**Notes:**
 * Inheritance works only with the generated types
 * Type can be inherited even from any type (from different files)
  * Type must be of same type to be extended, only `object` types can also inherit from
    `interface` type
 * `heirs` is the inverse of `inherits` section
 * Inheritance priority is defined by the order in the `inherits` section.
 * Inheritance use internally [array_replace_recursive](http://php.net/manual/en/function.array-replace-recursive.php) php function.
   for example `CharacterWizard` config is the result of
   `array_replace_recursive(CharacterConfig, CharacterWizardConfig)`

You can also create decorator types to be used as reusable templates.
Decorators are only virtual and will not exists in final schema.
That is the reason why decorator should never be reference as type in schema definition.

Here is an example of a decorator type:

```yaml
ObjectA:
    decorator: true
    config:
        fields:
            bar: {type: String!}
```
