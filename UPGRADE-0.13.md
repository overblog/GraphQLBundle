UPGRADE FROM 0.12 to 0.13
=======================

# Table of Contents

- [Rename default_field config](#rename-default_field-config)
- [Improve default field resolver](#improve-default-field-resolver)
- [Use service tags to register resolver maps](#use-service-tags-to-register-resolver-maps)

### Rename default_field config

```diff
overblog_graphql:
    definitions:
-       default_resolver: ~
+       default_field_resolver: ~
```

The new `default_field_resolver` config entry accepts callable service id.

### Improve default field resolver

Stop using internally `symfony/property-access` package
since it was a bottleneck to performance for large schema.

Array access and camelize getter are supported but isser, hasser,
jQuery style (e.g. `last()`) and "can" property accessors
are no more supported out-of-the-box,
please implement a custom resolver if these accessors are needed.

Globally:

```yaml
overblog_graphql:
    definitions:
        default_field_resolver: 'App\GraphQL\CustomResolver'
```

[see default field resolver for more details](https://webonyx.github.io/graphql-php/data-fetching/#default-field-resolver)

Per Type:

```yaml
MyType:
    type: object
    config:
        resolveField: 'App\GraphQL\MyTypeResolver::defaultFieldResolver'
        fields:
            name: {type: String}
            email: {type: String}
```

[see default Field Resolver per type for more details](https://webonyx.github.io/graphql-php/data-fetching/#default-field-resolver-per-type)

### Use service tags to register resolver maps

The resolver maps used to be configured using the `overblog_graphql.definitions.schema.resolver_maps`
option. This has been deprecated in favour of using service tags to register them.

```diff
# config/graphql.yaml
overblog_graphql:
    definitions:
        schema:
             # ...
-            resolver_maps:
-                - 'App\GraphQL\MyResolverMap'
```

```diff
# services/graphql.yaml
services:
-    App\GraphQL\MyResolverMap: ~
+    App\GraphQL\MyResolverMap:
+        tags:
+            - { name: overblog_graphql.resolver_map, schema: default }
```
