Union
=====

```yaml
# src/MyBundle/Resources/config/graphql/HumanAndDroid.types.yml
#
#  This implements the following type system shorthand:
#  union HumanAndDroid = Human | Droid
HumanAndDroid:
    type: union
    config:
        types: [Human, Droid]
        description: Human and Droid
```
