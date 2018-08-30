Scalars
=======

Here all supported built‐in Scalars:

* **Int**
* **Float**
* **String**
* **Boolean**
* **ID**

Custom Scalar
-------------

Here a simple example of how to add a custom Scalar:

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
     * @param \DateTimeInterface $value
     *
     * @return string
     */
    public static function serialize(\DateTimeInterface $value)
    {
        return $value->format('Y-m-d H:i:s');
    }
 
    /**
     * @param mixed $value
     *
     * @return \DateTimeInterface
     */
    public static function parseValue($value)
    {
        return new \DateTimeImmutable($value);
    }
 
    /**
     * @param Node $valueNode
     *
     * @return \DateTimeInterface
     */
    public static function parseLiteral(Node $valueNode)
    {
        return new \DateTimeImmutable($valueNode->value);
    }
}
```

If you prefer reusing a scalar type

```yaml
MyEmail:
    type: custom-scalar
    config:
        scalarType: '@=newObject("App\\Type\\EmailType")'
```
