# Union

## With YAML

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

## With annotations

Note: With annotations, you can omit the `types` parameter. If so, the system will try to detect GraphQL Type associated with classes that inherit or implement the Union class.  

```php
<?php

namespace AppBundle;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Union(types={"Human", "Droid"})
 * @GQL\Description("Human and Droid")
 */
class HumanAndDroid
{
}
```
