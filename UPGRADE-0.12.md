UPGRADE FROM 0.11 to 0.12
=======================

# Table of Contents

- [Remove auto mapping configuration](#remove-auto-mapping-configuration)
- [Relay Paginator, Connections & Edges](#relay-paginator-connections--edges)
- [Remove obsoletes deprecations](#remove-obsoletes-deprecations)
- [Simplify executor interface](#simplify-executor-interface)

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
       # config/services.yaml
       services:
           _defaults:
               autoconfigure: true

           App\GraphQL\:
               resource: ../src/GraphQL
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

Connection builder has been moved and it methods are no more accessible statically:

Before:

```php
use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;

ConnectionBuilder::connectionFromArray([]);
```

After:

```php
use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder;

$connectionBuilder = new ConnectionBuilder();
$connectionBuilder->connectionFromArray([]);
```

### Remove obsoletes deprecations

The builder short syntax (Field: Builder => Field: {builder: Builder}) is obsolete:

```diff
Foo:
    type: object
    config:
        fields:
-            bar: MyBuilder
+            bar: {builder: MyBuilder}

```

Relay builder without 'Relay::' prefix is obsolete:

```diff
Foo:
    type: object
    config:
        fields:
            bar:
-                argsBuilder: ConnectionArgs
+                argsBuilder: "Relay::Connection"
```

### Simplify executor interface

This section is only for users using custom executor.

The interface move to be look a little be more to `GraphQL\GraphQL`
`promiseToExecute` method.

In `Overblog\GraphQLBundle\Executor\ExecutorInterface`
`setPromiseAdapter` and `setDefaultFieldResolver` has been removed.

Promise adapter is now the first argument (`$promiseAdapter`)
and default field resolver the 7th argument (`$fieldResolver`) of
`Overblog\GraphQLBundle\Executor\ExecutorInterface::execute` method.
