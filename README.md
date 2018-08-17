OverblogGraphQLBundle
======================

[![Build Status](https://travis-ci.org/overblog/GraphQLBundle.svg?branch=master)](https://travis-ci.org/overblog/GraphQLBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/overblog/GraphQLBundle/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/overblog/GraphQLBundle/badge.svg?branch=master)](https://coveralls.io/github/overblog/GraphQLBundle?branch=master)
[![Latest Stable Version](https://poser.pugx.org/overblog/graphql-bundle/version)](https://packagist.org/packages/overblog/graphql-bundle)
[![Latest Unstable Version](https://poser.pugx.org/overblog/graphql-bundle/v/unstable)](https://packagist.org/packages/overblog/graphql-bundle)
[![Total Downloads](https://poser.pugx.org/overblog/graphql-bundle/downloads)](https://packagist.org/packages/overblog/graphql-bundle)

This Symfony bundle provides integration of [GraphQL](https://facebook.github.io/graphql/) using [webonyx/graphql-php](https://github.com/webonyx/graphql-php)
and [GraphQL Relay](https://facebook.github.io/relay/docs/graphql-relay-specification.html).
It also supports:
* batching with [ReactRelayNetworkLayer](https://github.com/nodkz/react-relay-network-layer)
* batching with [Apollo GraphQL](http://dev.apollodata.com/core/network.html#query-batching).
* upload and batching upload with [apollo-upload-client](https://github.com/jaydenseric/apollo-upload-client)

Browse your version documentation:

* [0.8  (OBSOLETE)](https://github.com/overblog/GraphQLBundle/blob/0.8/README.md)
* [0.9  (OBSOLETE)](https://github.com/overblog/GraphQLBundle/blob/0.9/README.md)
* [0.10 (STABLE)](https://github.com/overblog/GraphQLBundle/blob/0.10/README.md)
* [0.11 (STABLE)](https://github.com/overblog/GraphQLBundle/blob/0.11/README.md)
* [0.12 (DEV)](https://github.com/overblog/GraphQLBundle/blob/master/README.md)

[Versions requirements](src/Resources/doc/index.md#versions-requirements)

Documentation
-------------

- [Quick start](src/Resources/doc/definitions/quick-start.md)
- [Installation](src/Resources/doc/index.md)
- [Definitions](src/Resources/doc/definitions/index.md)
  - [Type System](src/Resources/doc/definitions/type-system/index.md)
    - [Scalars](src/Resources/doc/definitions/type-system/scalars.md)
    - [Object](src/Resources/doc/definitions/type-system/object.md)
    - [Interface](src/Resources/doc/definitions/type-system/interface.md)
    - [Union](src/Resources/doc/definitions/type-system/union.md)
    - [Enum](src/Resources/doc/definitions/type-system/enum.md)
    - [Input Object](src/Resources/doc/definitions/type-system/input-object.md)
    - [Lists](src/Resources/doc/definitions/type-system/lists.md)
    - [Non-Null](src/Resources/doc/definitions/type-system/non-null.md)
  - [Type Inheritance](src/Resources/doc/definitions/type-inheritance.md)
  - [GraphQL schema language](src/Resources/doc/definitions/graphql-schema-language.md)
  - [Schema](src/Resources/doc/definitions/schema.md)
  - [Resolver](src/Resources/doc/definitions/resolver.md)
  - [Solving N+1 problem](src/Resources/doc/definitions/solving-n-plus-1-problem.md)
  - [Mutation](src/Resources/doc/definitions/mutation.md)
  - [Relay](src/Resources/doc/definitions/relay/index.md)
    - [Connection](src/Resources/doc/definitions/relay/connection.md)
      - [Relay Pagination helper](src/Resources/doc/helpers/relay-paginator.md)
    - [Node](src/Resources/doc/definitions/relay/node/index.md)
      - [Node](src/Resources/doc/definitions/relay/node/node.md)
      - [Plural](src/Resources/doc/definitions/relay/node/plural.md)
      - [Global id](src/Resources/doc/definitions/relay/node/global-id.md)
    - [Mutation](src/Resources/doc/definitions/relay/mutation.md)
  - [Builders](src/Resources/doc/definitions/builders/index.md)
    - [Field Builder](src/Resources/doc/definitions/builders/field.md)
    - [Args Builder](src/Resources/doc/definitions/builders/args.md)
  - [Expression language](src/Resources/doc/definitions/expression-language.md)
  - [Debug](src/Resources/doc/definitions/debug/index.md)
  - [GraphiQL](src/Resources/doc/definitions/graphiql/index.md)
  - [Upload files](src/Resources/doc/definitions/upload-files.md)
- [Data fetching](src/Resources/doc/data-fetching/index.md)
  - [Query batching](src/Resources/doc/data-fetching/batching.md)
  - [Promise](src/Resources/doc/data-fetching/promise.md)
- [Security](src/Resources/doc/security/index.md)
  - [Handle CORS](src/Resources/doc/security/handle-cors.md)
  - [Object access control](src/Resources/doc/security/object-access-control.md)
  - [Fields access control](src/Resources/doc/security/fields-access-control.md)
  - [Fields public control](src/Resources/doc/security/fields-public-control.md)
  - [Limiting query depth](src/Resources/doc/security/limiting-query-depth.md)
  - [Query complexity analysis](src/Resources/doc/security/query-complexity-analysis.md)
  - [Disable introspection](src/Resources/doc/security/disable_introspection.md)
- [Errors handling](src/Resources/doc/error-handling/index.md)
- [Events](src/Resources/doc/events/index.md)

Talks and slides to help you start
----------------------------------

* GraphQL in Symfony *by Bernd Alter* - [Twitter](https://twitter.com/bazoo0815)
  - [Talk about GraphQL and its implementation with Symfony (26.04.2017)](https://www.slideshare.net/berndalter7/graphql-in-symfony) `English`
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
