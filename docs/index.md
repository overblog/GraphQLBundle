OverblogGraphQLBundle
======================

This Symfony bundle provides integration of [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php)
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).
It also supports batching using libs like [ReactRelayNetworkLayer](https://github.com/nodkz/react-relay-network-layer) or [Apollo GraphQL](http://dev.apollodata.com/core/network.html#query-batching).

Versions requirements
----------------------

| Version                                                         | PHP        | Symfony          | Support        |
| --------------------------------------------------------------: | ---------: | ---------------: | -------------: |
| [`1.0`](https://github.com/overblog/GraphQLBundle/tree/master)  | `>= 7.4`   | `>= 4.4`         | DEV            |
| [`0.13`](https://github.com/overblog/GraphQLBundle/tree/0.13)   | `>= 7.2`   | `>= 4.3`         | Active support |
| [`0.12`](https://github.com/overblog/GraphQLBundle/tree/0.12)   | `>= 7.1`   | `>= 3.4, <4.4`   | Active support |
| [`0.11`](https://github.com/overblog/GraphQLBundle/tree/0.11)   | `>= 5.6`   | `>= 3.1, <=4.3`  | Active support |
| [`0.10`](https://github.com/overblog/GraphQLBundle/tree/0.10)   | `>= 5.5.9` | `>= 2.8, <= 3.1` | End of life    |
| [`0.9`](https://github.com/overblog/GraphQLBundle/tree/0.9)     | `>= 5.5.9` | `>= 2.8, <= 3.1` | End of life    |
| [`0.8`](https://github.com/overblog/GraphQLBundle/tree/0.8)     | `>= 5.4 `  | `>= 2.7, <= 3.1` | End of life    |

After installation
------------

Define your [graphQL schema](definitions/index.md).

Symfony Flex installation
------------

**Note:** OverblogGraphQLBundle only supports Symfony Flex from version 0.9.0 onwards

**a)** Download the bundle

In the project directory:

```bash
composer require overblog/graphql-bundle
```

**b)** Accept the contrib recipes installation from Symfony Flex

```
-  WARNING  overblog/graphql-bundle (0.9): From github.com/symfony/recipes-contrib
    The recipe for this package comes from the "contrib" repository, which is open to community contributions.
    Do you want to execute this recipe?
    [y] Yes
    [n] No
    [a] Yes for all packages, only for the current installation session
    [p] Yes permanently, never ask again for this project
    (defaults to n): 
```

**c)** If you want to run GraphiQL interface we have a bundle:

 ```bash
 composer require --dev overblog/graphiql-bundle
 ```

Manual installation
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
overblog_graphql_single_endpoint:
    resource: "@OverblogGraphQLBundle/Resources/config/routing/single.yaml"
    prefix: /
```

To use multiple schemas:

```yaml
# in app/config/routing.yml
overblog_graphql_multiple_endpoint:
    resource: "@OverblogGraphQLBundle/Resources/config/routing/multiple.yaml"
    prefix: /graphql
```

Composer autoloader configuration (optional)
------------

Use composer `ClassLoader` to load generated class (optional but recommended)

Using composer ClassLoader will help keeping hand on loader optimization
in production environment...

First start by some configuration:

```yaml
overblog_graphql:
    definitions:
        # disable listener the bundle out of box classLoader
        use_classloader_listener: false
        # change to "false" to disable auto compilation.
        # To generate types manually, see "graphql:compile" command.
        auto_compile: true
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
