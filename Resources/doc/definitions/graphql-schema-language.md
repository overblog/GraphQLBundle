GraphQL schema language
=======================

This section we show how to define schema types using GraphQL schema language.
If you want to learn more about it, you can see
the [official documentation](http://graphql.org/learn/schema/)
or this [cheat sheet](https://github.com/sogko/graphql-shorthand-notation-cheat-sheet).

Here is an example:

```graphql
# config/graphql/schema.types.graphql

type Query {
  bar: Bar!
}

interface Foo {
  # Description of my is_foo field
  is_foo: Boolean
}
type Bar implements Foo {
  is_foo: Boolean
  is_bar: Boolean
}
```

What about resolvers? To define resolvers for needed fields
you must use decorators with `hiers` [inheritance feature](type-inheritance.md).

Here is how this can be done:

- First of all, enable both config files format (yaml or xml with graphql)

    ```yaml
    overblog_graphql:
        definitions:
            mappings:
                types:
                    types:
                        -
                            types: [graphql, yaml]
                            dir: "%kernel.project_dir%/config/graphql/types"
    ```

- Now you can write the decorators:

    ```yaml
    # config/graphql/resolvers.types.yaml

    QueryDecorator:
        decorator: true
        hiers: Query
        config:
            fields:
                bar: { resolve: "@=..." }

    FooDecorator:
        decorator: true
        hiers: Foo
        config:
            resolveType: "@=..."
    ```

**Notes:**

- This feature is experimental and could be improve or change in future releases
- Only type definition is allowed right now (excepted scalar type) using this shorthand syntax
- In general only required resolvers are the those managing fields on Root (query or mutation)
- Decorators is not limited to resolvers
