Input object
============

```yaml
# src/MyBundle/Resources/config/graphql/HumanAndDroid.types.yml
#
#  This implements the following type system shorthand:
#    type HeroInput {
#      name: Episode!
#   }
HeroInput:
    type: input-object
    config:
        fields:
            name:
                type: "Episode!"
```
