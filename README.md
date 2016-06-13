OverblogGraphQLBundle [WIP]
===========================

This Bundle provide integration [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php) 
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).

[![Build Status](https://travis-ci.org/overblog/GraphQLBundle.svg?branch=master)](https://travis-ci.org/overblog/GraphQLBundle) 
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/?branch=master) 
[![Code Coverage](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/?branch=master)

Requirements
------------
PHP >= 5.4

Installation
------------

**a)** Download the bundle

In the project directory:

```bash
composer require overblog/graphql-bundle
```

**b)** Enable the bundle

```php
// in app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Overblog\GraphQLBundle\OverblogGraphQLBundle(),
        ];

        // ...
    }
}
```

**c)** Enable GraphQL endpoint

```yaml
# in app/config/routing.yml
overblog_graphql_endpoint:
    resource: "@OverblogGraphQLBundle/Resources/config/routing/graphql.yml"
```

**d)** Enable GraphiQL in dev mode (required twig)

```yaml
# in app/config/routing_dev.yml
overblog_graphql_graphiql:
    resource: "@OverblogGraphQLBundle/Resources/config/routing/graphiql.yml"
```

Usage
-----

Schema Types can be defined in bundle Resources/config/graphql using this file extension **.types.yml** or **.types.xml**. 

### Types Definition

#### Enum

```yaml
# MyBundle/Resources/config/graphql/Episode.types.yml
# The original trilogy consists of three movies.
# This implements the following type system shorthand:
# enum Episode { NEWHOPE, EMPIRE, JEDI }
Episode:
    type: enum
    config:
        description: "One of the films in the Star Wars Trilogy"
        values:
            NEWHOPE:
                value: 4
                description: "Released in 1977."
            EMPIRE:
                value: 5
                description: "Released in 1980."
            JEDI:
                value: 6
                description: "Released in 1983."
```

#### Interface

```yaml
# src/MyBundle/Resources/config/graphql/Character.types.yml
# Characters in the Star Wars trilogy are either humans or droids.
#
# This implements the following type system shorthand:
#   interface Character {
#     id: String!
#     name: String
#     friends: [Character]
#     appearsIn: [Episode]
#   }
Character:
    type: interface
    config:
        description: "A character in the Star Wars Trilogy"
        fields:
            id:
                type: "String!"
                description: "The id of the character."
            name:
                type: "String"
                description: "The name of the character."
            friends:
                type: "[Character]"
                description: "The friends of the character."
            appearsIn:
                type: "[Episode]"
                description: "Which movies they appear in."
        # used expression language to defined resolver (tagged services)
        resolveType: "@=resolver('character_type', [value])"
```

```yaml
# src/MyBundle/Resources/config/services.yml
services:
    my.graph.resolver.character:
        class: MyBundle\GraphQL\Resolver\CharacterResolver
        arguments:
            - "@overblog_graphql.type_resolver"
        tags:
            - { name: overblog_graphql.resolver, alias: "character_type", method: "resolveType" }
            - { name: overblog_graphql.resolver, alias: "character_friends", method: "resolveFriends" }
            - { name: overblog_graphql.resolver, alias: "character_hero", method: "resolveHero" }
            - { name: overblog_graphql.resolver, alias: "character_human", method: "resolveHuman" }
            - { name: overblog_graphql.resolver, alias: "character_droid", method: "resolveDroid" }
```

```php
// src/MyBundle/GraphQL/Resolver
namespace MyBundle\GraphQL\Resolver;

require_once __DIR__ . '/../../../../vendor/webonyx/graphql-php/tests/StarWarsData.php';

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use GraphQL\StarWarsData;

class CharacterResolver implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    public function resolveType($data)
    {
        $typeResolver = $this->container->get('overblog_graphql.type_resolver');
    
        $humanType = $typeResolver->resolve('Human');
        $droidType = $typeResolver->resolve('Droid');
        
        $humans = StarWarsData::humans();
        $droids = StarWarsData::droids();
        if (isset($humans[$data['id']])) {
            return $humanType;
        }
        if (isset($droids[$data['id']])) {
            return $droidType;
        }
        return null;
    }
    
    public function resolveFriends($character)
    {
        return StarWarsData::getFriends($character);
    }
    
    public function resolveHero($args)
    {
        return StarWarsData::getHero(isset($args['episode']) ? $args['episode'] : null);
    }
    
    public function resolveHuman($args)
    {
        $humans = StarWarsData::humans();
        return isset($humans[$args['id']]) ? $humans[$args['id']] : null;
    }
    
    public function resolveDroid($args)
    {
        $droids = StarWarsData::droids();
        return isset($droids[$args['id']]) ? $droids[$args['id']] : null;
    }
}
```

#### Object

```yaml
# src/MyBundle/Resources/config/graphql/Human.types.yml
# We define our human type, which implements the character interface.
#
# This implements the following type system shorthand:
#   type Human : Character {
#     id: String!
#     name: String
#     friends: [Character]
#     appearsIn: [Episode]
#   }
Human:
    type: object
    config:
        description: "A humanoid creature in the Star Wars universe."
        fields:
            id:
                type: "String!"
                description: "The id of the character."
            name:
                type: "String"
                description: "The name of the character."
            friends:
                type: "[Character]"
                description: "The friends of the character."
                resolve: "@=resolver('character_friends', [value])"
            appearsIn:
                type: "[Episode]"
                description: "Which movies they appear in."
            homePlanet:
                type: "String"
                description: "The home planet of the human, or null if unknown."
        interfaces: [Character]
```

```yaml
# src/MyBundle/Resources/config/graphql/Droid.types.yml
#  The other type of character in Star Wars is a droid.
# 
#  This implements the following type system shorthand:
#    type Droid : Character {
#      id: String!
#      name: String
#      friends: [Character]
#      appearsIn: [Episode]
#      primaryFunction: String
#   }
Droid:
    type: object
    config:
        description: "A mechanical creature in the Star Wars universe."
        fields:
            id:
                type: "String!"
                description: "The id of the droid."
            name:
                type: "String"
                description: "The name of the droid."
            friends:
                type: "[Character]"
                description: "The friends of the droid, or an empty list if they have none."
                resolve: "@=resolver('character_friends', [value])"
            appearsIn:
                type: "[Episode]"
                description: "Which movies they appear in."
            primaryFunction:
                type: "String"
                description: "The primary function of the droid."
        interfaces: [Character]
```
### Union

```yaml
# src/MyBundle/Resources/config/graphql/HumanAndDroid.types.yml
#
#  This implements the following type system shorthand:
#  union HumanAndDroid = Human | Droid
HumanAndDroid:
    type: union
    config:
        types: [Human, Droid]
        description: Human and Droid
```

### Input object

```yaml
# src/MyBundle/Resources/config/graphql/HumanAndDroid.types.yml
#
#  This implements the following type system shorthand:
#    type HeroInput {
#      name: Episode!
#   }
HeroInput:
    type: input-object
    config:
        fields:
            name:
                type: "Episode!"
```

### Schema

```yaml
# src/MyBundle/Resources/config/graphql/Query.types.yml
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
                resolve: "@=resolver('character_hero', [args])"
            human:
                type: "Human"
                args:
                    id:
                        description: "id of the human"
                        type: "String!"
                resolve: "@=resolver('character_human', [args])"
            droid:
                type: "Droid"
                args:
                    id:
                        description: "id of the droid"
                        type: "String!"
                resolve: "@=resolver('character_droid', [args])"
```

```yaml
#app/config/config.yml

overblog_graphql:
    definitions:
        internal_error_message: "An error occurred, please retry later or contact us!"
        config_validation: %kernel.debug%
        schema:
            query: Query
            mutation: ~
```

### Relay

#### Connection

```yaml
Query:
    type: object
    config:
        fields:
            user:
                type: User
                resolve: '@=resolver("query")'

User:
    type: object
    config:
        fields:
            name:
                type: String
            friends:
                type: friendConnection
                argsBuilder: ConnectionArgs
                resolve: '@=resolver("friends", [value, args])'
            friendsForward:
                type: userConnection
                argsBuilder: ForwardConnectionArgs
                resolve: '@=resolver("friends", [value, args])'
            friendsBackward:
                type: userConnection
                argsBuilder: BackwardConnectionArgs
                resolve: '@=resolver("friends", [value, args])'

friendConnection:
    type: relay-connection
    config:
        nodeType: User
        resolveNode: '@=resolver("node", [value])'
        edgeFields:
            friendshipTime:
                type: String
                resolve: "Yesterday"
        connectionFields:
            totalCount:
                type: Int
                resolve: '@=resolver("connection")'

userConnection:
    type: relay-connection
    config:
        nodeType: User
        resolveNode: '@=resolver("node", [value])'
```

#### Mutation

```yaml
RootMutation:
    type: object
    config:
        fields:
            simpleMutation:
                builder: Mutation
                builderConfig:
                    inputType: simpleMutationInput
                    payloadType: simpleMutationPayload
                    mutateAndGetPayload: "@={'result': 1}"
            simpleMutationWithThunkFields:
                builder: Mutation
                builderConfig:
                    inputType: simpleMutationWithThunkFieldsInput
                    payloadType: simpleMutationWithThunkFieldsPayload
                    mutateAndGetPayload: "@={'result': value['inputData'] }"

simpleMutationInput:
    type: relay-mutation-input
    config:
        fields: []

simpleMutationWithThunkFieldsInput:
    type: relay-mutation-input
    config:
        fields:
            inputData : { type: "Int" }
            
simpleMutationPayload:
    type: relay-mutation-payload
    config:
        fields:
            result: { type: "Int" }

simpleMutationWithThunkFieldsPayload:
    type: relay-mutation-payload
    config:
        fields:
            result: { type: "Int" }
```

#### Node

```yaml
Query:
    type: object
    config:
        fields:
            node:
                builder: Node
                builderConfig:
                    nodeInterfaceType: Node
                    idFetcher: '@=resolver("node_id_fetcher", [value])'
                    
Node:
    type: relay-node
    config:
        resolveType: '@=resolver("node_type", [value])'

Photo:
    type: object
    config:
        fields:
            id:
                type: ID!
            width:
                type: Int
        interfaces: [Node]
        
User:
    type: object
    config:
        fields:
            id:
                type: ID!
            name:
                type: String
        interfaces: [Node]
```

##### Plural

```yaml
Query:
    type: object
    config:
        fields:
            usernames:
                builder: PluralIdentifyingRoot
                builderConfig:
                    argName: 'usernames'
                    description: 'Map from a username to the user'
                    inputType: String
                    outputType: User
                    resolveSingleInput: '@=resolver("plural_single_input", [value, info])'
                    
User:
    type: object
    config:
        fields:
            username:
                type: String
            url:
                type: String
```


##### Global

```yaml
Query:
    type: object
    config:
        fields:
            node:
                builder: Node
                builderConfig:
                    nodeInterfaceType: NodeInterface
                    idFetcher: '@=service("overblog_graphql.test.resolver.global").idFetcher(value)'
            allObjects:
                type: '[NodeInterface]'
                resolve: '@=service("overblog_graphql.test.resolver.global").resolveAllObjects()'

NodeInterface:
    type: relay-node
    config:
        resolveType: '@=service("overblog_graphql.test.resolver.global").typeResolver(value)'

User:
    type: object
    config:
        fields:
            id:
                builder: GlobalId
                builderConfig:
                    typeName: User
            name:
                type: String
        interfaces: [NodeInterface]

Photo:
    type: object
    config:
        fields:
            id:
                builder: GlobalId
                builderConfig:
                    typeName: Photo
                    idFetcher: '@=value["photoId"]'
            width:
                type: Int
        interfaces: [NodeInterface]

Post:
    type: object
    config:
        fields:
            id:
                builder: GlobalId
                builderConfig:
                    typeName: Post
            text:
                type: String
        interfaces: [NodeInterface]
```

Error Handling
--------------

In no debug mode all errors will be logged and replace by a generic error message.
Only query parsed error will not be replace.
If you want to send explicit error or warnings messages to your users you can use exceptions:

1- **Overblog\\GraphQLBundle\\Error\\UserError** to send unique error

```php
use Overblog\GraphQLBundle\Error\UserError

class CharacterResolver
{
    //...
    public function resolveHuman($args)
    {
        $humans = StarWarsData::humans();

        if (!isset($humans[$args['id']])) {
            throw new UserError(sprintf('Could not find Human#%d', $args['id']));
        }

        return $humans[$args['id']];
    }
    //...
}
```

2- **Overblog\\GraphQLBundle\\Error\\UserWarning** to send unique warning

```php
use Overblog\GraphQLBundle\Error\UserWarning

class CharacterResolver
{
    //...
    public function resolveHuman($args)
    {
        $humans = StarWarsData::humans();

        if (!isset($humans[$args['id']])) {
            throw new UserWarning(sprintf('Could not find Human#%d', $args['id']));
        }

        return $humans[$args['id']];
    }
    //...
}
```

Warnings can be found in the response under `extensions.warnings` map.

3- **Overblog\\GraphQLBundle\\Error\\UserErrors** to send multiple errors

```php
use Overblog\GraphQLBundle\Error\UserError
use Overblog\GraphQLBundle\Error\UserErrors

class CharacterResolver
{
    //...
    public function resolveHumanAndDroid($args)
    {
        $humans = StarWarsData::humans();
        
        $errors = [];

        if (!isset($humans[$args['human_id']])) {
            $errors[] = new UserError(sprintf('Could not find Human#%d', $args['human_id']));
        }

        $droids = StarWarsData::droids();

        if (!isset($droids[$args['droid_id']])) {
            $errors[] = sprintf('Could not find Droid#%d', $args['droid_id']);
        }

        if (!empty($errors)) {
            throw new UserErrors($errors);
        }

        return [
            'human' => $humans[$args['human_id']],
            'droid' => $droids[$args['droid_id']],
        ];
    }
    //...
}
```

Security
--------

### Access Control


An access control can be add on each field using `config.fields.*.access` or globally with `config.fieldsDefaultAccess`.
If `config.fields.*.access` value is true field will be normally resolved but will be `null` otherwise.
Act like access is`true` if not set.

In the example below the Human name is available only for authenticated users.

```yaml
Human:
    type: object
    config:
        description: "A humanoid creature in the Star Wars universe."
        fields:
            id:
                type: "String!"
                description: "The id of the character."
            name:
                type: "String"
                description: "The name of the character."
                access: "@=isAuthenticated()"
            friends:
                type: "[Character]"
                description: "The friends of the character."
                resolve: "@=resolver('character_friends', [value])"
            appearsIn:
                type: "[Episode]"
                description: "Which movies they appear in."
            homePlanet:
                type: "String"
                description: "The home planet of the human, or null if unknown."
        interfaces: [Character]
```

### Query Complexity Analysis 

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

### Limiting Query Depth

This is a PHP port of [Limiting Query Depth](http://sangria-graphql.org/learn/#limiting-query-depth) in Sangria implementation.
Introspection query with description max depth is **7**.

```yaml
#app/config/config.yml
overblog_graphql:
    security:
        query_max_depth: 10
```

Default value `false` disabled validation.

Field builder
-------------

Builder is a way to don't repeat field definition.

Define your custom field builder
```yaml
#app/config/config.yml
overblog_graphql:
    #... 
    definitions:
        #...
        builders:
            field:
                -
                    alias: "RawId"
                    class: "MyBundle\\GraphQL\\Field\\RawIdField"
```

Builder class must implements `Overblog\GraphQLBundle\Definition\Builder\MappingInterface`

```php
namespace MyBundle\GraphQL\Field;

use Overblog\GraphQLBundle\Definition\Builder\MappingInterface;

class RawIdField implements MappingInterface
{
    public function toMappingDefinition(array $config)
    {
        return [
            'description' => 'The raw ID of an object',
            'type' => 'Int!',
            'resolve' => '@=value.id',
        ];
    }
}
```

usage:

```yaml
#Resources/graphql/schema.yml
User:
    type: object
    config:
        fields:
            # equivalent to rawId: { description: "The user raw id", type: 'Int!', resolve: "@=value.id"  }
            rawId:
                builder: "RawId"
                description: "The user raw id"

Post:
    type: object
    config:
        fields:
            # equivalent to rawId: { description: "The raw ID of an object", type: 'Int!', resolve: "@=value.id"  }
            rawId: "RawId"
```

Args builder
------------

TODO

Expression language
-------------------

Some configs entries can use expression language but it must be explicitly triggered using "@=" like prefix.

**a)** Compatible configs entries 

- config.fields.*.access
- config.fields.\*.args.\*.defaultValue
- config.fields.*.deprecationReason
- config.fields.*.resolve
- config.idFetcher
- config.isTypeOf
- config.mutateAndGetPayload
- config.resolveCursor
- config.resolveType 
- config.resolveSingleInput
- config.values.*.value

**b)** Functions description

Expression | Description | Usage 
---------- | ----------- | -----
object **service**(string $id) | Get a service from the container | @=service('my_service').customMethod()
mixed **parameter**(string $name) | Get parameter from the container | @=parameter('kernel.debug')
boolean **isTypeOf**(string $className) | Verified if `value` is instance of className | @=isTypeOf('AppBundle\\User\\User')
mixed **resolver**(string $alias, array $args = []) | call the method on the tagged service "overblog_graphql.resolver" with args | @=resolver('blog_by_id', [value['blogID']])
mixed **mutation**(string $alias, array $args = []) | call the method on the tagged service "overblog_graphql.mutation" with args | @=mutation('remove_post_from_community', [value])
string **globalId**(string\|int id, string $typeName = null) | Relay node globalId | @=globalId(15, 'User')
array **fromGlobalId**(string $globalId) | Relay node fromGlobalId | @=fromGlobalId('QmxvZzox')
object **newObject**(string $className, array $args = []) | Instantiation $className object with $args | @=newObject('AppBundle\\User\\User', ['John', 15])
boolean **hasRole**(string $role) | Checks whether the token has a certain role. | @=hasRole('ROLE_API')
boolean **hasAnyRole**(string $role1, string $role2, ...string $roleN) | Checks whether the token has any of the given roles. | @=hasAnyRole('ROLE_API', 'ROLE_ADMIN')
boolean **isAnonymous**() | Checks whether the token is anonymous. | @=isAnonymous()
boolean **isRememberMe**() | Checks whether the token is remember me. | @=isRememberMe()
boolean **isFullyAuthenticated**() | Checks whether the token is fully authenticated. | @=isFullyAuthenticated()
boolean **isAuthenticated**() | Checks whether the token is not anonymous. | @=isAuthenticated()
boolean **hasPermission**(mixed $var, string $permission) | Checks whether the token has the given permission for the given object (requires the ACL system). |@=hasPermission(object, 'OWNER')
boolean **hasAnyPermission**(mixed $var, array $permissions) | Checks whether the token has any of the given permissions for the given object | @=hasAnyPermission(object, ['OWNER', 'ADMIN'])

**c)** Variables description

Expression | Description | Scope
---------- | ----------- | --------
**container** | DI container | global
**request** | Refers to the current request. | Request
**token** | Refers to the token which is currently in the security token storage. Token can be null. | Token
**user** | Refers to the user which is currently in the security token storage. User can be null. | Valid Token
**object** | Refers to the value of the field for which access is being requested. For array `object` will be each item of the array. For Relay connection `object` will be the node of each connection edges. | only available for `config.fields.*.access` with query operation or mutation payload type.
**value** | Resolver value | only available in resolve context 
**args** | Resolver args array | only available in resolve context 
**info** | Resolver GraphQL\Type\Definition\ResolveInfo Object | only available in resolve context
**childrenComplexity** | Selection field children complexity | only available in complexity context

[For more details on expression syntax](http://symfony.com/doc/current/components/expression_language/syntax.html)

**Tips**: the expression language service can be custom using bundle configuration.

Contribute
----------

Tests:

Install [phpunit](https://phpunit.de/manual/current/en/installation.html).

In the bundle directory:

```bash
phpunit
```

Fix PHP CS:

```bash
vendor/bin/php-cs-fixer fix ./
```
