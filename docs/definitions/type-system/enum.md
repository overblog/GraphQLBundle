# Enum

## With YAML

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
                # in previous versions this was done with '@=constant("App\\StarWars\\Movies::MOVIE_EMPIRE")'
                value: !php/const App\StarWars\Movies::MOVIE_EMPIRE
                description: "Released in 1980."
            JEDI: 6 # using the short syntax (JEDI value equal to 6)
            FORCEAWAKENS: # in this case FORCEAWAKENS value = FORCEAWAKENS
                description: "Released in 2015."
```

## With Annotations

Note: At the moment, doctrine annotations on constants are not supported. So if you need to add config like description or deprecationReason, you must add @GQL\EnumValue with the constant name as name attribute on the annotation.

```php
<?php

namespace AppBundle;

use App\StarWars\Movies;
use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Enum(values={
 *    @GQL\EnumValue(name="NEWHOPE", description="Released in 1977."),
 *    @GQL\EnumValue(name="EMPIRE", description="Released in 1980."),
 *    @GQL\EnumValue(name="FORCEAWAKENS", description="Released in 2015."),
 * })
 * @GQL\Description("One of the films in the Star Wars Trilogy")
 */
class Episode
{
    const NEWHOPE = 4;
    const EMPIRE = Movies::MOVIE_EMPIRE;
    const JEDI = 6;
    const FORCEAWAKENS = 'FORCEAWAKENS';
    
    public $value;
}
```
