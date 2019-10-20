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

Builder class must implement `Overblog\GraphQLBundle\Definition\Builder\MappingInterface`

```php
<?php

namespace MyBundle\GraphQL\Field;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class RawIdField implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $name = $config['name'] ?? 'id';
        $type = $config['type'] ?? 'Int!';

        return [
            'description' => 'The raw ID of an object',
            'type' => $type,
            'resolve' => "@=value.$name",
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
            rawId:
                builder: "RawId"
                description: "The user raw id"

Post:
    type: object
    config:
        fields:
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
                description: 'The user raw id'
                type: 'Int!'
                resolve: '@=value.id'

Post:
    type: object
    config:
        fields:
            rawId:
                description: "The raw ID of an object"
                type: 'String'
                resolve: "@=value.photoID"
```

## Adding new types related to the field

Field builder can also generate additional types. Simply return an array in the following form from your builder:

```php
<?php
# \MyBundle\GraphQL\Field\MyFieldBuilder::toMappingDefinition
return [
    'field' => [
        // your field configuration, like in the example above
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

Example implementation field builder with new types:

```php
<?php

namespace Overblog\GraphQLBundle\Tests\DependencyInjection\Builder;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class MutationField implements MappingInterface
{
    public function toMappingDefinition(array $config): array
    {
        $name = $config['name'] ?? null;
        $resolver = $config['resolver'] ?? null;
        $inputFields = $config['inputFields'] ?? [];

        $successPayloadFields = $config['payloadFields'] ?? null;
        $failurePayloadFields = [
            '_error' => ['type' => 'String'],
        ];

        foreach (\array_keys($inputFields) as $fieldName) {
            $failurePayloadFields[$fieldName] = ['type' => 'String'];
        }

        $payloadTypeName = $name.'Payload';
        $payloadSuccessTypeName = $name.'SuccessPayload';
        $payloadFailureTypeName = $name.'FailurePayload';
        $inputTypeName = $name.'Input';

        $field = [
            'type' => $payloadTypeName.'!',
            'resolve' => \sprintf('@=mutation("%s", [args["input"]])', $resolver),
            'args' => [
                'input' => $inputTypeName.'!',
            ],
        ];

        $types = [
            $inputTypeName => [
                'type' => 'input-object',
                'config' => [
                    'fields' => $inputFields,
                ],
            ],
            $payloadTypeName => [
                'type' => 'union',
                'config' => [
                    'types' => [$payloadSuccessTypeName, $payloadFailureTypeName],
                    'resolveType' => \sprintf(
                        '@=resolver("PayloadTypeResolver", [value, "%s", "%s"])',
                        $payloadSuccessTypeName,
                        $payloadFailureTypeName
                    ),
                ],
            ],
            $payloadSuccessTypeName => [
                'type' => 'object',
                'config' => [
                    'fields' => $successPayloadFields,
                ],
            ],
            $payloadFailureTypeName => [
                'type' => 'object',
                'config' => [
                    'fields' => $failurePayloadFields,
                ],
            ],
        ];

        return ['field' => $field, 'types' => $types];
    }
}
```

Example usage field builder with new types:

```yaml
Mutation:
    type: object
    config:
        fields:
            foo:
                builder: 'Mutation'
                builderConfig:
                    name: 'Foo'
                    resolver: 'Mutation.foo'
                    inputFields:
                        bar: {type: 'String!'}
                    payloadFields:
                        fooString: {type: 'String!'}
```

Would produce the following configuration:

```yaml
Mutation:
    type: object
    config:
        fields:
            foo:
                type: 'FooPayload!'
                resolve: '@=mutation("Mutation.foo", [args["input"]])'
                args:
                    input: {type: 'FooInput!'}

FooInput:
    type: input-object
    config:
        fields:
            bar: {type: 'String!'}

FooPayload:
    type: union
    config:
        types: ['FooSuccessPayload', 'FooFailurePayload']
        resolveType: '@=resolver("PayloadTypeResolver", [value, "FooSuccessPayload", "FooFailurePayload"])'

FooSuccessPayload:
    type: object
    config:
        fields:
            fooString: {type: 'String!'}
            
FooFailurePayload:
    type: object
    config:
        fields:
            _error: {type: 'String'}
            bar: {type: 'String'}
```
