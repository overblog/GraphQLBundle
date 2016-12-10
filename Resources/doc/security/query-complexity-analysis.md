Query Complexity Analysis
=========================

This is a PHP port of [Query Complexity Analysis](http://sangria-graphql.org/learn/#query-complexity-analysis) in Sangria implementation.
Introspection query with description max complexity is **109**.

Define your max accepted complexity:

```yaml
#app/config/config.yml
overblog_graphql:
    security:
        query_max_complexity: 1000
```

Default value `false` disabled validation.

Customize your field complexity using `config.fields.*.complexity`

```yaml
# src/MyBundle/Resources/config/graphql/Query.types.yml

Query:
    type: object
    config:
        fields:
            droid:
                type: "Droid"
                complexity: '@=1000 + childrenComplexity'
                args:
                    id:
                        description: "id of the droid"
                        type: "String!"
                resolve: "@=resolver('character_droid', [args])"
```

In the example we add `1000` on the complexity every time using `Query.droid` field in query.
Complexity function signature: `function (int $childrenComplexity = 0, array $args = [])`.
