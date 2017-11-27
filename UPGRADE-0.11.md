UPGRADE FROM 0.10 to 0.11
=======================

# Table of Contents

- [GraphiQL](#graphiql)

### GraphiQL

 * The GraphiQL interface has been removed in favor of a new bundle.

  Upgrading:
   - Remove the graphiql route from your application
     - For standard Symfony installation: `/app/config/routing_dev.yml`
     - For Symfony Flex: `/config/routes/dev/graphql_graphiql.yaml`
   - Installing OverblogGraphiQLBundle
     - `composer require --dev overblog/graphiql-bundle`
     - Follow instructions at https://github.com/overblog/GraphiQLBundle
