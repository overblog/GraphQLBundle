Enum
====

```yaml
# MyBundle/Resources/config/graphql/Episode.types.yml
# The original trilogy consists of three movies.
# This implements the following type system shorthand:
# enum Episode { NEWHOPE, EMPIRE, JEDI }
Episode:
    type: enum
    config:
        description: "One of the films in the Star Wars Trilogy"
        values:
            NEWHOPE:
                value: 4
                description: "Released in 1977."
                # to deprecate a value, only set the deprecation reason
                #deprecationReason: "Just because"
            EMPIRE:
                value: 5
                description: "Released in 1980."
            JEDI: 6 # using the short syntax (JEDI value equal to 6)
#           in this case FORCEAWAKENS value = FORCEAWAKENS
#            FORCEAWAKENS:
#                description: "Released in 2015."
```
