UPGRADE FROM 0.12 to 0.13
=======================

# Table of Contents

- [Improve default field resolver](#improve-default-field-resolver)

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
        default_resolver: 'App\GraphQL\CustomResolver::defaultFieldResolver'
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
