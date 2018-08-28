Schema
=======

Default files location
-------

**Symfony Flex:**

- ***Main configuration:*** `/config/packages/graphql.yaml`
- ***Types:*** `/config/graphql/types/query.yaml`
- ***Routes:*** `/config/routes/graphql.yaml`

**Symfony Standard:**

- ***Main configuration:*** `/app/config/config.yml`
- ***Types:*** `src/MyBundle/Resources/config/graphql/Query.types.yml`
- ***Routes:*** `/app/config/routing.yml`

Yaml configuration
-------

For more examples on what can be done with Symfony Expression Language (the stuff after `@=`), check
[here](expression-language.md) and [here](http://symfony.com/doc/current/components/expression_language/syntax.html).

```yaml
# This is the type that will be the root of our query, and the
# entry point into our schema. It gives us the ability to fetch
# objects by their IDs, as well as to fetch the undisputed hero
# of the Star Wars trilogy, R2-D2, directly.
#
# This implements the following type system shorthand:
#   type Query {
#     hero(episode: Episode): Character
#     human(id: String!): Human
#     droid(id: String!): Droid
#   }
#
Query:
    type: object
    config:
        description: "A humanoid creature in the Star Wars universe."
        fields:
            hero:
                type: "Character"
                args:
                    episode:
                        description: "If omitted, returns the hero of the whole saga. If provided, returns the hero of that particular episode."
                        type: "Episode"
                resolve: "@=resolver('character_hero', [args['episode'].getId()])"
            human:
                type: "Human"
                args:
                    id:
                        description: "id of the human"
                        type: "String!"
                resolve: "@=resolver('character_human', [args['id']])"
            droid:
                type: "Droid"
                args:
                    id:
                        description: "id of the droid"
                        type: "String!"
                resolve: "@=resolver('character_droid', [args])"
```

Or using annotation:

```php
<?php

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * Class RootQuery
 *
 * @GQL\GraphQLType(type="object")
 * @GQL\GraphQLDescription(description="A humanoid creature in the Star Wars universe.")
 */
class RootQuery
{
    /**
     * @GQL\GraphQLColumn(type="Character")
     * @GQL\GraphQLQuery(
     *     method="character_hero",
     *     args={
     *         "args['episode'].getId()"
     *     }
     * )
     */
    public $hero;

    /**
     * @GQL\GraphQLColumn(type="Human")
     * @GQL\GraphQLQuery(method="character_human", args={"args['id']"})
     */
    public $human;
    
    /**
     * @GQL\GraphQLColumn(type="Droid")
     * @GQL\GraphQLQuery(method="character_human", args={"args"})
     */
    public $droid;
}
```


```yaml
overblog_graphql:
    definitions:
        schema:
            query: Query
            mutation: ~
            # the name of extra types that can not be detected
            # by graphql-php during static schema analysis.
            # These types names should be explicitly declare here
            types: []
```

## Batching


Batching can help decrease io between server and client.
The default route of batching is `/batch`.

## Multiple schema endpoint

```yaml
overblog_graphql:
    definitions:
        schema:
            foo:
                query: fooQuery
            bar:
                query: barQuery
                mutation: barMutation
```

**foo** schema endpoint can be access:

type | Path
-----| -----
simple request | `/graphql/foo`
batch request | `/graphql/foo/batch`
GraphiQL* | `/graphiql/foo`

**bar** schema endpoint can be access:

type | Path
-----| -----
simple request | `/graphql/bar`
batch request | `/graphql/bar/batch`
GraphiQL* | `/graphiql/bar`

\* `/graphiql` depends on [OverblogGraphiQLBundle](https://github.com/overblog/GraphiQLBundle)
