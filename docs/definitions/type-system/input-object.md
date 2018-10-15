# Input object

## With YAML

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
                type: "String!"
```

## With Annotations

Note: If the attribute `name` is not set on the `@GQL\Input`, the final name will be the class name suffixed by "Input" if it doesn't have already the suffix. (ex: If the class name is `Hero` the input name will be `HeroInput`).  
With Input type, the `@Field` annotation on methods are ignored, so is the annotations on properties that need a resolver.

```php
<?php

namespace AppBundle;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Input
 */
class HeroInput
{
    /**
     * @GQL\Field(type="String!")
     */
    public $name;
}
```
