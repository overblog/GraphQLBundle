OverblogGraphQLBundle
======================

This Symfony 2 / 3 bundle provide integration [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php)
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).
It also supports batching using libs like [ReactRelayNetworkLayer](https://github.com/nodkz/react-relay-network-layer) or [Apollo GraphQL](http://dev.apollodata.com/core/network.html#query-batching).

Requirements
------------
PHP >= 5.5

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

**e)** Use composer ClassLoader to load generated class (optional but recommended)

Using composer ClassLoader will help keeping hand on loader optimization
in production environment...

First start by some configuration:

```yaml
overblog_graphql:
    definitions:
        # disable listener the bundle out of box classLoader
        use_classloader_listener: false
        # change classes cache dir (recommends using a directory that will be committed)
        cache_dir: "/my/path/to/my/generated/classes"
        # Can also change the namespace
        #class_namespace: "Overblog\\GraphQLBundle\\__DEFINITIONS__"
```

then enable composer autoloader in project `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "Overblog\\GraphQLBundle\\__DEFINITIONS__\\": "my/path/to/my/generated/classes/"
        }
    }
}
```

Finish by dumping the new autoloader.

```bash
composer dump-autoload
```

Now you can define your [graphQL schema](definitions/index.md).
