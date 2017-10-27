UPGRADE FROM 0.9 to 0.10
=======================

# Table of Contents

- [Symfony]($symfony)
- [GraphiQL](#graphiql)

### Symfony
 
 * Minimal supported Symfony version is now `^3.1 || ^4.0` 
   
   We've dropped support for Symfony 2.8 and 3.0
   
  Upgrading your Symfony version:
   - [Upgrading to Symfony 3.0](https://github.com/symfony/symfony/blob/master/UPGRADE-3.0.md)
   - [Upgrading to Symfony 3.1](https://github.com/symfony/symfony/blob/master/UPGRADE-3.1.md)

### GraphiQL

 * The GraphiQL interface has been removed in favor of a new bundle.

  Upgrading:
   - Remove the graphiql route from your application
     - For standard Symfony installation: `/app/config/routing_dev.yml`
     - For Symfony Flex: `/config/routes/dev/graphql_graphiql.yaml`
   - Installing OverblogGraphiQLBundle
     - `composer require overblog/graphiql-bundle`
     - Follow instructions at https://github.com/overblog/GraphiQLBundle
