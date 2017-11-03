OverblogGraphQLBundle
======================

This Symfony 2 / 3 bundle provide integration [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php)
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).
It also supports batching using libs like [ReactRelayNetworkLayer](https://github.com/nodkz/react-relay-network-layer) or [Apollo GraphQL](http://dev.apollodata.com/core/network.html#query-batching).

[![Build Status](https://travis-ci.org/overblog/GraphQLBundle.svg?branch=master)](https://travis-ci.org/overblog/GraphQLBundle) 
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/?branch=master) 
[![Coverage Status](https://coveralls.io/repos/github/overblog/GraphQLBundle/badge.svg?branch=master)](https://coveralls.io/github/overblog/GraphQLBundle?branch=master)

Documentation
-------------

- [Installation](Resources/doc/index.md)
- [Definitions](Resources/doc/definitions/index.md)
  - [Type System](Resources/doc/definitions/type-system/index.md)
    - [Scalars](Resources/doc/definitions/type-system/scalars.md)
    - [Object](Resources/doc/definitions/type-system/object.md)
    - [Interface](Resources/doc/definitions/type-system/interface.md)
    - [Union](Resources/doc/definitions/type-system/union.md)
    - [Enum](Resources/doc/definitions/type-system/enum.md)
    - [Input Object](Resources/doc/definitions/type-system/input-object.md)
    - [Lists](Resources/doc/definitions/type-system/lists.md)
    - [Non-Null](Resources/doc/definitions/type-system/non-null.md)
  - [Schema](Resources/doc/definitions/schema.md)
  - [Resolver](Resources/doc/definitions/resolver.md)
  - [Mutation](Resources/doc/definitions/mutation.md)
  - [Relay](Resources/doc/definitions/relay/index.md)
    - [Connection](Resources/doc/definitions/relay/connection.md)
      - [Relay Pagination helper](Resources/doc/helpers/relay-paginator.md)
    - [Node](Resources/doc/definitions/relay/node/index.md)
      - [Node](Resources/doc/definitions/relay/node/node.md)
      - [Plural](Resources/doc/definitions/relay/node/plural.md)
      - [Global id](Resources/doc/definitions/relay/node/global-id.md)
    - [Mutation](Resources/doc/definitions/relay/mutation.md)
  - [Builders](Resources/doc/definitions/builders/index.md)
    - [Field Builder](Resources/doc/definitions/builders/field.md)
    - [Args Builder](Resources/doc/definitions/builders/args.md)
  - [Expression language](Resources/doc/definitions/expression-language.md)
  - [Debug](Resources/doc/definitions/debug/index.md)
  - [GraphiQL](Resources/doc/definitions/graphiql/index.md)
    - [Custom HTTP headers](Resources/doc/definitions/graphiql/custom-http-headers.md)
- [Data fetching](Resources/doc/data-fetching/index.md)
  - [Query batching](Resources/doc/data-fetching/batching.md)
  - [Promise](Resources/doc/data-fetching/promise.md)
- [Security](Resources/doc/security/index.md)
  - [Handle CORS](Resources/doc/security/handle-cors.md)
  - [Fields access control](Resources/doc/security/fields-access-control.md)
  - [Fields public control](Resources/doc/security/fields-public-control.md)
  - [Limiting query depth](Resources/doc/security/limiting-query-depth.md)
  - [Query complexity analysis](Resources/doc/security/query-complexity-analysis.md)
  - [Errors handling](Resources/doc/security/errors-handling.md)

Community
---------

* Get some support on [Symfony devs Slack](https://symfony.com/slack-invite)
  on the dedicated channel **overblog-graphql**.
* Follow us on [GitHub](https://github.com/overblog)

Contributing
------------

[See contributing documentation](CONTRIBUTING.md)
