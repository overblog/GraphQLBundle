Disable introspection
=====================

This bundle supports [webonyx/graphql-php validation rule to disable introspection queries](http://webonyx.github.io/graphql-php/security/#disabling-introspection).

Introspection is a mechanism for fetching schema structure. It is used by tools like GraphiQL for auto-completion, query validation, etc.

It means that anybody can get a full description of your schema by sending a special query containing meta fields __type and __schema.

If you are not planning to expose your API to the general public, it makes sense to disable this feature in production. By disabling, tools like GraphiQL won't work anymore.

```yaml
#app/config/config.yml
overblog_graphql:
    security:
        enable_introspection: '%kernel.debug%'
```

Introspection is enabled by default.
