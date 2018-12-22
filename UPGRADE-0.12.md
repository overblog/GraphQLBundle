UPGRADE FROM 0.11 to 0.12
=======================

# Table of Contents

- [Remove auto mapping configuration](#remove-auto-mapping-configuration)

### Remove auto mapping configuration

 * The AutoMapping configuration entry has been removed in favor of Symfony 4+ service configuration.

  Upgrading:
   - Delete old configuration.
        ```diff
        overblog_graphql:
            definitions:
        -        auto_mapping: ~
        ```
   - use Symfony 4+ service configuration to tag your types, resolvers or mutation.
       ```yaml
       # config/graphql.yaml
       services:
           _defaults:
               autowire: true
               public: true
       
           _instanceof:
               GraphQL\Type\Definition\Type:
                   tags: ['overblog_graphql.type']
               Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface:
                   tags: ['overblog_graphql.resolver']
               Overblog\GraphQLBundle\Definition\Resolver\MutationInterface:
                   tags: ['overblog_graphql.mutation']
       
           App\GraphQL\:
               resource: ../GraphQL
       ```


### Relay Paginator, Connections & Edges

-   Following the [paginator update](docs/helpers/relay-paginator.md) and the use of interfaces for Relay Connection & Edge, getters & setters must be use to manipulate Connection, Edge and PageInfo Properties

Before :

```php
$connection->edges = $edges;
$connection->totalCount = 10;
...
$edge->cursor = $cursor;
$edge->node = $node;

```

After :

```php
$connection->setEdges($edges);
$connection->setTotalCount(10);
...
$edge->setCursor($cursor);
$edge->setNode($node);
```

Connection builder method are no more accessible statically:

Before:

```php
use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder;

ConnectionBuilder::connectionFromArray([]);
```

After:

```php
use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder;

$connectionBuilder = new ConnectionBuilder();
$connectionBuilder->connectionFromArray([]);
```
