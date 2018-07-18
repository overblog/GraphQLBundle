Input object
============

```yaml
# src/MyBundle/Resources/config/graphql/HumanAndDroid.types.yml
#
#  This implements the following type system shorthand:
#    input HeroInput {
#      name: Episode!
#   }
HeroInput:
    type: input-object
    config:
        fields:
            name:
                type: "Episode!"
```

Or with annotation:

```php
<?php

/**
 * @\Overblog\GraphQLBundle\Annotation\GraphQLType(type="input-object")
 * @\Overblog\GraphQLBundle\Annotation\GraphQLDescription(description="One of the films in the Star Wars Trilogy")
 */
class HeroInput
{
    /**
     * @\Overblog\GraphQLBundle\Annotation\GraphQLToOne(target="Episode", nullable=false)
     */
    public $episode;
}
```