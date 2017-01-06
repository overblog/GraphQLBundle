OverblogGraphQLBundle
======================

This Symfony 2 / 3 bundle provide integration [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php)
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).
It also supports batching using libs like [ReactRelayNetworkLayer](https://github.com/nodkz/react-relay-network-layer).

[![Build Status](https://travis-ci.org/overblog/GraphQLBundle.svg?branch=master)](https://travis-ci.org/overblog/GraphQLBundle) 
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/?branch=master) 
[![Code Coverage](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/?branch=master)

Documentation
-------------

For documentation, see:

    doc/

[Read the documentation](doc/index.md)

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
vendor/bin/php-cs-fixer fix .
```
