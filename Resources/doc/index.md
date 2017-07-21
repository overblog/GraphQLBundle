OverblogGraphQLBundle
======================

This Symfony 2 / 3 bundle provide integration [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php)
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).
It also supports batching using libs like [ReactRelayNetworkLayer](https://github.com/nodkz/react-relay-network-layer) or [Apollo GraphQL](http://dev.apollodata.com/core/network.html#query-batching).

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

Now you can define your [graphQL schema](definitions/index.md).