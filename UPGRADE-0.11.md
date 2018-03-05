UPGRADE FROM 0.10 to 0.11
=======================

# Table of Contents

- [GraphiQL](#graphiql)
- [Errors handler](#errors-handler)
- [Promise adapter interface](#promise-adapter-interface)
- [Expression language](#expression-language)
- [Type autoMapping and Symfony DI autoconfigure](#type-automapping-and-symfony-di-autoconfigure)
- [Events](#events)
- [Explicitly declare non detected types](#explicitly-declare-non-detected-types)
- [Change fluent resolvers id](#change-fluent-resolvers-id)

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
### Type autoMapping and Symfony DI `autoconfigure`

   When using these functionality, type will be accessible only by FQCN in schema definition,
   (if class not implementing `Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface`).
   So if you want to use the `true` type name don't forget to declare it as an alias using interface.
   This change is for a performance mater types are lazy loaded.

   example:

   ```php
   <?php

   namespace App\GraphQL\Type;

   use GraphQL\Type\Definition\ScalarType;

   class DateTimeType extends ScalarType
   {
       public $name = 'DateTime';
       // ...
   }
   ```
   **Before 0.11**: DateTimeType could be accessed by FQCN `App\GraphQL\Type\DateTimeType` and the real `DateTimeType`.

   **Since 0.11**: Only FQCN `App\GraphQL\Type\DateTimeType` is accessible

   here how this can be done in 0.11:

   ```php
   <?php

   namespace App\GraphQL\Type;

   use GraphQL\Type\Definition\ScalarType;
   use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;

   class DateTimeType extends ScalarType implements AliasedInterface
   {
       public $name = 'DateTime';

       /**
        * {@inheritdoc}
        */
       public static function getAliases()
       {
           return ['DateTime'];
       }
       // ...
   }
   ```

### Explicitly declare non detected types

   **Before 0.11** all types was declare as non detected types, this was not the correct way of declaring types.
   This could lead to some performances issues or/and wrong types public exposition (in introspection query).
   [See webonyx/graphql-php documentations for more details](http://webonyx.github.io/graphql-php/type-system/schema/#configuration-options)

   **Since 0.11** Non detect types should be explicitly declare

   here a concrete example:
   ```yaml
   Query:
      type: object
      config:
         fields:
            foo: {type: FooInterface!}

   FooInterface:
      type: interface
      config:
         fields:
            id: {type: ID!}
         resolveType: '@=resolver("foo", [value])'

   Bar:
      type: object
      config:
         fields:
            id: {type: ID!}
            # ...
         interfaces: [FooInterface]

   Baz:
      type: object
      config:
         fields:
            id: {type: ID!}
            # ...
         interfaces: [FooInterface]
   ```
   In above example `Baz` an `Bar` can not be detected by graphql-php during static schema analysis,
   an `GraphQL\Error\InvariantViolation` exception will be throw with the following message:
   ```text
   Could not find possible implementing types for FooInterface in schema.
   Check that schema.types is defined and is an array of all possible types in the schema.
   ```
   here how this can be fix:

   ```yaml
   overblog_graphql:
      definitions:
         schema:
            query: Query
            types: [Bar, Baz]
   ```

### Events

  `Overblog\GraphQLBundle\Event\ExecutorContextEvent::setExecutorContext` method has been removed as `context`
  is now a `ArrayObject`. When using `graphql.executor.context` listener the value will now be accessible only
  in `context` variables and not in `rootValue`. `context` and `rootValue` has been separate, if you need to
  use `rootValue` see [event documentation for more details](Resources/doc/events/index.md).

  **Before 0.11**
  `context` and `rootValue` were of type `array` with same value so `$context === $info->rootValue` and
  `$context === $value` in root query resolver. That for the reason why uploaded files was accessible in
  `$context['request_files']` and `$info->rootValue['request_files']`.

  **Since 0.11**
  `context` is of type `ArrayObject` and `rootValue` has no typeHint (default: `null`) so
  `$context !== $info->rootValue` and `$context !== $value` in root query resolver.
  Uploaded files is no more accessible under `$info->rootValue['request_files']` out of the box.

### Change fluent resolvers id

  The use of class name as prefix of fluent resolver id remove the possibility to use same class as 2 different services.
  See issue [#296](https://github.com/overblog/GraphQLBundle/issues/296) for more detail
  That's the reason why starting v0.11 we are using service id as prefix (like in Symfony 4.1)...

  Example:
  ```yaml
  services:
      app.resolver.greetings:
          class: App\GraphQL\Resolver\Greetings
          tags:
              - { name: overblog_graphql.resolver, method: __invoke, alias: say_hello }
              - { name: overblog_graphql.resolver }
  ```

  **Before 0.11**: `'@=resolver("App\\GraphQL\\Resolver\\Greetings", [args['name']])'`

  **Since 0.11**: `'@=resolver("app.resolver.greetings", [args['name']])'`
