OverblogGraphQLBundle
======================

This Symfony bundle provides integration of [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php)
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).
It also supports batching using libs like [ReactRelayNetworkLayer](https://github.com/nodkz/react-relay-network-layer) or [Apollo GraphQL](http://dev.apollodata.com/core/network.html#query-batching).

[![Build Status](https://travis-ci.org/overblog/GraphQLBundle.svg?branch=master)](https://travis-ci.org/overblog/GraphQLBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/overblog/GraphQLBundle/badge.svg?branch=master)](https://coveralls.io/github/overblog/GraphQLBundle?branch=master)
[![Latest Stable Version](https://poser.pugx.org/overblog/graphql-bundle/version)](https://packagist.org/packages/overblog/graphql-bundle)
[![Latest Unstable Version](https://poser.pugx.org/overblog/graphql-bundle/v/unstable)](https://packagist.org/packages/overblog/graphql-bundle)
[![Total Downloads](https://poser.pugx.org/overblog/graphql-bundle/downloads)](https://packagist.org/packages/overblog/graphql-bundle)

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
  - [Solving N+1 problem](Resources/doc/definitions/solving-n-plus-1-problem.md)
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

Talks and slides to help you start
----------------------------------

* GraphQL is right in front of us, let's to it! *by Renato Mendes Figueiredo* - [Twitter](https://twitter.com/renatomefi), [GitHub](https://github.com/renatomefi)
  - [Slides at http://talks.mefi.in/graphql-scotphp17](http://talks.mefi.in/graphql-scotphp17/) `English`
  - [Video at SymfonyCamp UA 2017](https://www.youtube.com/watch?v=jyoYlnCPNgk) `English`
  - [Video at DPC 2017](https://www.youtube.com/watch?v=E7MjoCOGSSY) `English`
* A GraphQL API: From hype to production *by Aurélien David* - [Twitter](https://twitter.com/spyl94), [GitHub](https://github.com/spyl94)
  - [Slides at https://spyl.net/slides/symfonycon-cluj-2017](https://spyl.net/slides/symfonycon-cluj-2017) `English`
* Une API GraphQL: du hype à la prod *by Aurélien David* - [Twitter](https://twitter.com/spyl94), [GitHub](https://github.com/spyl94)
  - [Video at PHPTour 2017 Nantes](https://www.youtube.com/watch?v=xbipW6fgD6c) `French`
* Introduction to Symfony Flex and setting up OverblogGraphQLBundle with it *by Renato Mendes Figueiredo* - [Twitter](https://twitter.com/renatomefi), [GitHub](https://github.com/renatomefi)
  - [Slides at http://talks.mefi.in/symfony-flex-101-symfonycampua](http://talks.mefi.in/symfony-flex-101-symfonycampua/) `English`
  - [Video at Symfony Camp UA 2017](https://www.youtube.com/watch?v=lWweoiCI9Hk) `English`

Community
---------

* Get some support on [Symfony devs Slack](https://symfony.com/slack-invite)
  on the dedicated channel **overblog-graphql**.
* Follow us on [GitHub](https://github.com/overblog)

Contributing
------------

* [See contributing documentation](CONTRIBUTING.md)
* [Thanks to all contributors](https://github.com/overblog/GraphQLBundle/graphs/contributors)
