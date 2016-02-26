OverblogGraphQLBundle
=======================

[![Build Status](https://travis-ci.com/overblog/GraphQLBundle.svg?token=PdnF6Q2whDtzNCCrCqfi&branch=master)](https://travis-ci.com/overblog/GraphQLBundle)

Description
-----------

This Bundle provide integration [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php) 
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).

Requirements
------------
PHP >= 5.4

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

**c)** Enable GraphiQL in dev mode (required twig)

```yaml
# in app/config/routing_dev.yml
overblog_graphql_graphiql:
    resource: "@OverblogGraphQLBundle/Resources/config/routing/graphiql.yml"
```

Usage
-----

Schema Types can be defined in bundle Resources/config/graphql using this file extension **.types.yml** or **.types.xml**. 

TODO

Contribute
----------

Fix PHP CS:

```
vendor/bin/php-cs-fixer fix ./ --level=symfony --fixers=header_comment,align_double_arrow
```
