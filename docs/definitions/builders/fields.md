# Fields builder

Builder is a way to don't repeat fields definition.

Define your custom fields builder

```yaml
#app/config/config.yml
overblog_graphql:
    #...
    definitions:
        #...
        builders:
            fields:
                - alias: "Timestamped"
                  class: "MyBundle\\GraphQL\\Fields\\TimestampFields"
```

Builder class must implements `Overblog\GraphQLBundle\Definition\Builder\MappingInterface`

```php
<?php

namespace MyBundle\GraphQL\Field;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class TimestampFields implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        $propertyCreatedAt = isset($config['propertyCreatedAt']) ? $config['propertyCreatedAt'] : 'createdAt';
        $propertyUpdatedAt = isset($config['propertyUpdatedAt']) ? $config['propertyUpdatedAt'] : 'updatedAt';

        return [
            'createdAt' => [
                'description' => 'The creation date of the object',
                'type' => 'Int!',
                'resolve' => '@=value.' . $propertyCreatedAt,
            ],
            'updatedAt' => [
                'description' => 'The update date of the object',
                'type' => 'Int!',
                'resolve' => '@=value.'. $propertyUpdatedAt,
            ],
        ];
    }
}
```

usage:

```yaml
User:
    type: object
    config:
        builders:
            - builder: Timestamped
              builderConfig:
                  propertyCreated: dateCreated
```

this is equivalent to:

```yaml
User:
    type: object
    config:
        fields:
            createdAt:
                description: The creation date of the object
                type: Int!
                resolve: "@=value.dateCreated"
            updatedAt:
                description: The update date of the object
                type: Int!
                resolve: "@=value.updatedAt"
```
