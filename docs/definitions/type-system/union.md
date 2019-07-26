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
