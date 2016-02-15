OverblogGraphQLBundle
=======================

Installation
------------

a) Download the bundle

In the project directory:

```
composer require overblog/graphql-bundle
```

b) Enabled the bundle

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

c) Enable graphQl endpoint

```yaml
# in app/config/routing.yml
overblog_graphql:
    resource: "@OverblogGraphQLBundle/Resources/config/routing.yml"
```

Usage
-----

TODO
