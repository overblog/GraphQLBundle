Object access Control
======================

If your GraphQL schema has multiple paths to the same resolver, you may end up with duplicated access control on the different fields leading to this resolver.

Access control can be added to an object as a whole using a decorator type for this protected field and make every parent extend this type.


Access control can be added to individual fields using `config.fields.*.access` or globally with `config.fieldsDefaultAccess`.
If the value returned by `config.fields.*.access` is true, the field will be resolved normally, and `null` will be returned otherwise.
If not set, acts like if access is `true`.

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
