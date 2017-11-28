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
   - In case you have defined the `versions` in your configuration
     - Remove it from `overblog_graphql`
         ```diff
         overblog_graphql:
         -    versions:
         -        graphiql: "0.11"
         -        react: "15.6"
         -        fetch: "2.0"
         -        relay: "classic"
         ```
     - Add it to `overblog_graphiql`
         ```diff
         overblog_graphiql:
         +    javascript_libraries:
         +        graphiql: "0.11"
         +        react: "15.6"
         +        fetch: "2.0"
        ```
     - If you were using the `graphql:dump-schema` and depending on the `relay`
     version as in the previous configuration, now you have to explicitly choose
     for a format during the command:
        ```
        bin/console graphql:dump-schema --modern
        ```
