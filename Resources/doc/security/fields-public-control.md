Fields public Control
=====================

You can use `config.fields.*.public` to control if a field needs to be removed the results.
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
                public: "@=service('security.authorization_checker').isGranted('ROLE_ADMIN')"

```

You can also use `config.fieldsDefaultPublic` to handle the setting globally on an object :

```yaml
AnObject:
    type: object
    fieldsDefaultPublic: "@=service('my_service').isGranted(typeName, fieldName)"
    config:
        fields:
            id:
                type: "String!"
            privateData:
                type: "String"
```

Have you noticed `typeName` and `fieldName` here ? This variables are always set to the current
type name and current field name, meaning you can apply a per field `public` setting on all the
fields with one line of yaml.
