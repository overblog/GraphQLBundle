# Fields public Control

## With YAML

You can use `config.fields.*.public` to control if a field needs to be removed from the results.
If `config.fields.*.public` value is true or is not set, the field will be visible.
If value is false, then the field will be removed (in any query, including inspection queries).

In the example below the `privateData` field will be available only for users with the role `ROLE_ADMIN`.

```yaml
AnObject:
    type: object
    config:
        fields:
            id:
                type: "String!"
            privateData:
                type: "String"
                public: "@=isGranted('ROLE_ADMIN')"
```

## With Annotations

```php
<?php

namespace App\Entity\GraphQLType;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * Class FormErrorType
 *
 * @GQL\GraphQLType(type="object")
 */
class AnObject
{
    /**
     * @GQL\GraphQLColumn(type="string")
     */
    public $id;

    /**
     * @GQL\GraphQLColumn(type="string")
     * @GQL\GraphQLPublicControl(method="service('security.authorization_checker').isGranted('ROLE_ADMIN')")
     */
    public $privateData;
}
```

You can also use `config.fieldsDefaultPublic` to handle the setting globally on an object:

```yaml
AnObject:
    type: object
    config:
        fieldsDefaultPublic: "@=service('my_service').isGranted(typeName, fieldName)"
        fields:
            id:
                type: "String!"
            privateData:
                type: "String"
```

Have you noticed `typeName` and `fieldName` here? These variables are always set to the current
type name and current field name, meaning you can apply a per field `public` setting on all the
fields with one line of yaml.

## Input object fields

`public` is also supported on `input-object` fields. When the expression resolves to `false`,
the field is removed from the input type — it is hidden from introspection and rejected if a
client tries to submit it.

```yaml
HeroInput:
    type: input-object
    config:
        fields:
            name:
                type: "String!"
            internalNote:
                type: "String"
                public: "@=isGranted('ROLE_ADMIN')"
```

With attributes, combine `#[GQL\Field]` with `#[GQL\IsPublic]` on the property:

```php
<?php

use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Input]
class HeroInput
{
    #[GQL\Field(type: "String!")]
    public string $name;

    #[GQL\Field(type: "String")]
    #[GQL\IsPublic("isGranted('ROLE_ADMIN')")]
    public ?string $internalNote = null;
}
```
