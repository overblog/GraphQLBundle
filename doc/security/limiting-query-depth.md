Limiting query depth
====================

This is a PHP port of [Limiting Query Depth](http://sangria-graphql.org/learn/#limiting-query-depth) in Sangria implementation.
Introspection query with description max depth is **7**.

```yaml
#app/config/config.yml
overblog_graphql:
    security:
        query_max_depth: 10
```

Default value `false` disabled validation.
