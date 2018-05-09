Object access Control
======================

If your GraphQL schema have multiple paths to the same resolver, you may end up with duplicated access control on the different fields leading to this resolver.

An access control can be added on the whole object using a decorator type for this protected field and make every parent extend this type.


An access control can be added on each field using `config.fields.*.access` or globally with `config.fieldsDefaultAccess`.
If `config.fields.*.access` value is true field will be normally resolved but will be `null` otherwise.
Act like access is`true` if not set.

In the example below the user field protection is set by the decorator:

```yaml
ProtectedUser:
  type: object
  decorator: true
  config:
      fields: 
        user: {type: User, access: '@=isAuthenticated()'}

Foo:
  type: object
  inherits: [ProtectedUser] 
  config:
      fields: 
        other: {type: String!}

Bar:
  type: object
  inherits: [ProtectedUser] 
```
