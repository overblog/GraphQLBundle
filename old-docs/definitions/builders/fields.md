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

Builder class must implement `Overblog\GraphQLBundle\Definition\Builder\MappingInterface`

```php
<?php

namespace MyBundle\GraphQL\Field;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class TimestampFields implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $propertyCreatedAt = $config['propertyCreatedAt'] ?? 'createdAt';
        $propertyUpdatedAt = $config['propertyUpdatedAt'] ?? 'updatedAt';

        return [
            'createdAt' => [
                'description' => 'The creation date of the object',
                'type' => 'Int!',
                'resolve' => "@=value.$propertyCreatedAt",
            ],
            'updatedAt' => [
                'description' => 'The update date of the object',
                'type' => 'Int!',
                'resolve' => "@=value.$propertyUpdatedAt",
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

## Adding new types related to the fields

Fields builder can also generate additional types. Simply return an array in the following form from your builder:

```php
<?php
# \MyBundle\GraphQL\Fields\MyFieldsBuilder::toMappingDefinition
return [
    'fields' => [
        // your fields configuration, like in the example above
    ],
    'types' => [
        'NewType1' => [
            'type' => 'object',
            // your type configuration, like in a *.types.yaml file ...
        ],
        'NewType2' => [
            'type' => 'enum',
            // your type configuration, like in a *.types.yaml file ...
        ],    
    ],
];
```

There are a few rules you have to take into account:

- New type(s) may not override existing types from configs (exception will be thrown)
- Builder may not output new type(s) more than once (exception will be thrown)
- New types will not have their builder configs processed. The type(s) must be complete (will result in exception).

Example implementation fields builder with new types:

```php
<?php

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class BoxFields implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $mapping = [];

        foreach ($config as $boxField => $itemType) {
            $boxType = $itemType.'Box';

            $mapping['fields'][$boxField] = ['type' => $boxType.'!'];
            $mapping['types'][$boxType] = [
                'type' => 'object',
                'config' => [
                    'fields' => [
                        'isEmpty' => ['type' => 'Boolean!'],
                        'item' => ['type' => $itemType],
                    ],
                ],
            ];
        }

        return $mapping;
    }
}
```

Example usage fields builder with new types:

```yaml
Boxes:
    type: object
    config:
        builders:
            - builder: 'Boxes'
              builderConfig:
                foo: 'Foo'
                bar: 'Bar'
```

Would produce the following configuration:

```yaml
Boxes:
    type: object
    config:
        fields:
            foo: {type: 'FooBox!'}
            bar: {type: 'BarBox!'}
            
FooBox:
    type: object
    config:
        fields:
            isEmpty: {type: 'Boolean!'}
            item: {type: 'Foo'}
            
BarBox:
    type: object
    config:
        fields:
            isEmpty: {type: 'Boolean!'}
            item: {type: 'Bar'}
```
