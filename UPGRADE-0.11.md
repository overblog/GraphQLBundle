UPGRADE FROM 0.10 to 0.11
=======================

# Table of Contents

- [GraphiQL](#graphiql)
- [Errors handler](#errors-handler)
- [Promise adapter interface](#promise-adapter-interface)
- [Expression language](#expression-language)

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

### Errors Handler

  * Made errors handler more customizable

  Upgrading:
   - User
   - Delete configuration to override base user exception classes.
        ```diff
        overblog_graphql:
            definitions:
                exceptions:
        -           types:
        -               warnings: ~
        -               errors: ~
        ```
   - Move `internal_error_message`, `map_exceptions_to_parent` and `exceptions` configurations
   from `definitions` to new dedicated `error_handler` section.
        ```diff
        overblog_graphql:
            definitions:
        -       internal_error_message: ~
        -       map_exceptions_to_parent: ~
        -       exceptions: ~
        +   errors_handler:
        +      internal_error_message: ~
        +      map_exceptions_to_parent: ~
        +      exceptions: ~
        ```


### Promise adapter interface

  * Changed the promise adapter interface (`Overblog\GraphQLBundle\Executor\ExecutorInterface`)
  as the promiseAdapter is not nullable in the bundle context.

  Upgrading:
   - `setPromiseAdapter` method no more nullable.
        ```diff
        - public function setPromiseAdapter(PromiseAdapter $promiseAdapter = null);
        + public function setPromiseAdapter(PromiseAdapter $promiseAdapter);
        ```

### Expression language

  * **user** expression variable has been replaced by **getUser** expression function
  * **container**, **request** and **token** expression variables has been removed.
    `service` or `serv` expression function should be used instead.

  Upgrading your schema configuration:
   - Replace `user` by `getUser()`:
        ```diff
        - resolve: '@=user'
        + resolve: '@=getUser()'
        ```

        or

        ```diff
        - resolve: '@=resolver('foo', [user])'
        + resolve: '@=resolver('foo', [getUser()])'
        ```
   - Replace `token` by `serv('security.token_storage')`
        ```diff
        - resolve: '@=token'
        + resolve: '@=serv('security.token_storage')'
        ```

        or

        ```diff
        - resolve: '@=resolver('foo', [token])'
        + resolve: '@=resolver('foo', [serv('security.token_storage')])'
        ```
   - Replace `request` by `serv('request_stack')`
        ```diff
        - resolve: '@=request'
        + resolve: '@=serv('request_stack')'
        ```

        or

        ```diff
        - resolve: '@=resolver('foo', [request])'
        + resolve: '@=resolver('foo', [serv('request_stack')])'
        ```
