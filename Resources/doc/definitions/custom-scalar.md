# Custom scalar

Here a simple example to add a custom scalar:

```yaml
DateTime:
    type: custom-scalar
    config:
        serialize: ["AppBundle\\DateTimeType", "serialize"]
        parseValue: ["AppBundle\\DateTimeType", "parseValue"]
        parseLiteral: ["AppBundle\\DateTimeType", "parseLiteral"]
```

```php
<?php

namespace AppBundle;

use GraphQL\Language\AST\Node;

class DateTimeType
{
    /**
     * @param \DateTime $value
     *
     * @return string
     */
    public static function serialize(\DateTime $value)
    {
        return $value->format('Y-m-d H:i:s');
    }
 
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function parseValue($value)
    {
        return new \DateTime($value);
    }
 
    /**
     * @param Node $valueNode
     *
     * @return string
     */
    public static function parseLiteral($valueNode)
    {
        return new \DateTime($valueNode->value);
    }
}
```
