# Scalars

Here all supported built‐in Scalars:

-   **Int**
-   **Float**
-   **String**
-   **Boolean**
-   **ID**

## Custom Scalar

### With YAML

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

### With annotation

You can create your custom-scalar type using the GraphQLType annotation with only one class.
For example:

```php
<?php

namespace AppBundle;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * Class DatetimeType
 *
 * @GQL\Scalar(name="DateTime")
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
