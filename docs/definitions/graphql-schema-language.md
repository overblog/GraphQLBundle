GraphQL schema language
=======================

This section we show how to define schema types using GraphQL schema language.
If you want to learn more about it, you can see
the [official documentation](http://graphql.org/learn/schema/)
or this [cheat sheet](https://github.com/sogko/graphql-shorthand-notation-cheat-sheet).

#### Configuration

```
overblog_graphql:
    definitions:
        schema:
            # ...
        mappings:
            types:
                -
                    type: graphql
                    dir: "%kernel.project_dir%/config/graphql/types"
```

#### Usage

##### Define Types

> [GraphQL documentation about types and fields](https://graphql.github.io/learn/schema/#object-types-and-fields).


```graphql
# config/graphql/types/schema.types.graphql

type Character {
  # Name of the character
  name: String! 
  # This character appears in those episodes
  appearsIn: [Episode]!
}
```

##### Define Enumeration types

> [GraphQL documentation about Enumerations types](https://graphql.github.io/learn/schema/#enumeration-types).

```graphql
# Enumeration of episodes
enum Episode {
  NEWHOPE @deprecated
  EMPIRE
  JEDI
}
```

##### Define Interfaces

> [GraphQL documentation about Interfaces](https://graphql.github.io/learn/schema/#interfaces).

```graphql
interface Character {
  id: ID!
  name: String!
  friends: [Character] @deprecated(reason: "This field was deprecated!")
  appearsIn: [Episode]!
}
```

```graphql
type Human implements Character {
  id: ID!
  name: String!
  friends: [Character]
  appearsIn: [Episode]!
  starships: [Starship]
  totalCredits: Int
}

type Droid implements Character {
  id: ID!
  name: String!
  friends: [Character]
  appearsIn: [Episode]!
  primaryFunction: String
}
```

##### Define queries

> [GraphQL documentation about Query type](https://graphql.github.io/learn/schema/#the-query-and-mutation-types).
 
```graphql
type RootQuery {
  # Access all characters
  characters: [Character]!
}
```

Do not forget to configure your schema **query** type, as described in the [schema documentation](https://github.com/overblog/GraphQLBundle/blob/master/Resources/doc/definitions/schema.md).

```yml
overblog_graphql:
    definitions:
        schema:
            query: RootQuery
```

##### Define mutations

> [GraphQL documentation about Mutation type](https://graphql.github.io/learn/schema/#the-query-and-mutation-types).

```graphql
input CreateCharacter {
  name: String!
}

input UpdateCharacter {
  name: String!
}

type RootMutation {
  createCharacter(character: CreateCharacter!): Character!
  updateCharacter(characterId: ID!, character: UpdateCharacter!): Character!
}
```

Do not forget to configure your schema **mutation** type, as described in the [schema documentation](https://github.com/overblog/GraphQLBundle/blob/master/Resources/doc/definitions/schema.md).

```yml
overblog_graphql:
    definitions:
        schema:
            mutation: RootMutation
```

---

When using this shorthand syntax, you define your field resolvers (and some more configuration) separately
from the schema. Since the schema already describes all of the fields, arguments, and result types, the only
thing left is a collection of callable that are called to actually execute these fields.
This can be done using [resolver-map](resolver-map.md).

**Notes:**
- This feature is experimental and could be improve or change in future releases
- Only type definition is allowed right now using this shorthand syntax
- The definition of schema root query or/and mutation should still be done in
[main configuration file](schema.md).
