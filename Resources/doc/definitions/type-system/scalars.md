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

### Yaml

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

If you prefer reusing a scalar type

```yaml
MyEmail:
    type: custom-scalar
    config:
        scalarType: '@=newObject("App\\Type\\EmailType")'
```

### Annotation

You can create your custom-scalar type using the GraphQLType annotation with only one class.
For example:

```php
<?php

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * Class DatetimeType
 *
 * @GQL\GraphQLAlias(name="datetime")
 * @GQL\GraphQLType(type="custom-scalar")
 */
class DatetimeType
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