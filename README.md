OverblogGraphQLBundle
=======================

Description
-----------

This Bundle provide integration [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php) 
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).

Installation
------------

**a)** Download the bundle

In the project directory:

```
composer require overblog/graphql-bundle
```

**b)** Enabled the bundle

```php
// in app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Overblog\GraphQLBundle\OverblogGraphQLBundle(),
        );

        // ...
    }
}
```

**c)** Enable graphQL endpoint

```yaml
# in app/config/routing.yml
overblog_graphql:
    resource: "@OverblogGraphQLBundle/Resources/config/routing.yml"
```

Usage
-----

TODO
