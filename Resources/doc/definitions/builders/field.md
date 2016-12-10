Field builder
=============

Builder is a way to don't repeat field definition.

Define your custom field builder
```yaml
#app/config/config.yml
overblog_graphql:
    #... 
    definitions:
        #...
        builders:
            field:
                -
                    alias: "RawId"
                    class: "MyBundle\\GraphQL\\Field\\RawIdField"
```

Builder class must implements `Overblog\GraphQLBundle\Definition\Builder\MappingInterface`

```php
namespace MyBundle\GraphQL\Field;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class RawIdField implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        return [
            'description' => 'The raw ID of an object',
            'type' => 'Int!',
            'resolve' => '@=value.id',
        ];
    }
}
```

usage:

```yaml
#Resources/graphql/schema.yml
User:
    type: object
    config:
        fields:
            # equivalent to rawId: { description: "The user raw id", type: 'Int!', resolve: "@=value.id"  }
            rawId:
                builder: "RawId"
                description: "The user raw id"

Post:
    type: object
    config:
        fields:
            # equivalent to rawId: { description: "The raw ID of an object", type: 'Int!', resolve: "@=value.id"  }
            rawId: "RawId"
```
