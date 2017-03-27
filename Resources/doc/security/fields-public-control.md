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
