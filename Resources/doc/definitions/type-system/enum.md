Enum
====

```yaml
# MyBundle/Resources/config/graphql/Episode.types.yml
# The original trilogy consists of three movies.
# This implements the following type system shorthand:
# enum Episode { NEWHOPE, EMPIRE, JEDI, FORCEAWAKENS }
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
                # We can use a PHP constant to avoid a magic number
                value: '@=constant("App\\StarWars\\Movies::MOVIE_EMPIRE")'
                description: "Released in 1980."
            JEDI: 6 # using the short syntax (JEDI value equal to 6)
            FORCEAWAKENS: # in this case FORCEAWAKENS value = FORCEAWAKENS
                description: "Released in 2015."
```

Or with annotation:

```php
<?php

/**
 * @\Overblog\GraphQLBundle\Annotation\GraphQLType(type="enum")
 * @\Overblog\GraphQLBundle\Annotation\GraphQLDescription(description="One of the films in the Star Wars Trilogy")
 */
class Episode
{
    /**
     * @\Overblog\GraphQLBundle\Annotation\GraphQLDescription(description="Released in 1977.")
     */
    const NEWHOPE = 4;
    
    /**
     * @\Overblog\GraphQLBundle\Annotation\GraphQLDescription(description="Released in 1980.")
     */
    const EMPIRE = 'constant("App\\StarWars\\Movies::MOVIE_EMPIRE")';
    
    const JEDI = 6;
    
    /**
     * @\Overblog\GraphQLBundle\Annotation\GraphQLDescription(description="Released in 2015.")
     */
    const FORCEAWAKENS = 'FORCEAWAKENS';
}
```