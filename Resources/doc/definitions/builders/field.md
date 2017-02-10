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
#               using short syntax
#               RawId: "MyBundle\\GraphQL\\Field\\RawIdField"
```

Builder class must implements `Overblog\GraphQLBundle\Definition\Builder\MappingInterface`

```php
<?php

namespace MyBundle\GraphQL\Field;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class RawIdField implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $name = isset($config['name']) ? $config['name'] : 'id';
        $type = isset($config['type']) ? $config['type'] : 'Int!';

        return [
            'description' => 'The raw ID of an object',
            'type' => $type,
            'resolve' => '@=value.'.$name,
        ];
    }
}
```

usage:

```yaml
User:
    type: object
    config:
        fields:
            # equivalent to => rawId: { description: "The user raw id", type: 'Int!', resolve: "@=value.id"  }
            rawId:
                builder: "RawId"
                description: "The user raw id"

Post:
    type: object
    config:
        fields:
            # equivalent to => rawId: { description: "The raw ID of an object", type: 'String', resolve: "@=value.photoID"  }
            rawId: 
                builder: "RawId"
                #config your builder
                builderConfig:
                    name: photoID
                    type: String
```

this is equivalent to:

```yaml
User:
    type: object
    config:
        fields:
            rawId:
                description: "The user raw id"
                type: 'Int!'
                resolve: "@=value.id"

Post:
    type: object
    config:
        fields:
            rawId:
                description: "The raw ID of an object"
                type: 'String'
                resolve: "@=value.photoID"
```
