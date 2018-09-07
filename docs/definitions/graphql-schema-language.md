GraphQL schema language
=======================

This section we show how to define schema types using GraphQL schema language.
If you want to learn more about it, you can see
the [official documentation](http://graphql.org/learn/schema/)
or this [cheat sheet](https://github.com/sogko/graphql-shorthand-notation-cheat-sheet).

#### Usage

```graphql
# config/graphql/schema.types.graphql

type Query {
  bar: Foo!
  baz(id: ID!): Baz
}

scalar Baz

interface Foo {
  # Description of my is_foo field
  is_foo: Boolean
}
type Bar implements Foo {
  is_foo: Boolean
  user: User!
  deprecatedField: String! @deprecated(reason: "This field was deprecated!")
}

enum User {
  TATA
  TITI
  TOTO @deprecated
}
```

When using this shorthand syntax, you define your field resolvers (and some more configuration) separately
from the schema. Since the schema already describes all of the fields, arguments, and result types, the only
thing left is a collection of callable that are called to actually execute these fields.
This can be done using [resolver-map](resolver-map.md).

**Notes:**
- This feature is experimental and could be improve or change in future releases
- Only type definition is allowed right now using this shorthand syntax
- The definition of schema root query or/and mutation should still be done in
[main configuration file](schema.md).
