UPGRADE FROM 0.14 to 1.0
=========================

* Removed `use_experimental_executor` configuration option.
* Signature of TypeInterface changed from `string $name = null, string $resolveType` to `string $resolveType, ?string $name = null`
* Removed deprecated `targetType` from `Query`
* Removed deprecated `fieldBuilder` from `Field`
* Removed deprecated `argsBuilder` from `Field`
* Removed deprecated `args` from `Field`
* Removed deprecated `builders` from `Type`
* Removed deprecated `values` from `Enum`
* Removed deprecated `resolver_maps` configuration option
* Removed `request_files` from context, use `request` object instead
* Removed deprecated `builder` from `FieldsBuilder`, use `name` attribute instead
* Removed deprecated `builderConfig` from `FieldsBuilder`, use `config` attribute instead
* Removed deprecated autowire for type, resolver or mutation
  implementing `Symfony\Component\DependencyInjection::setContainer`, use  Symfony native autowire config instead
* All classes are now final. If you need an extension point, try to use composition, 
  implementing the interface or raise an issue.

UPGRADE FROM 0.13 to 0.14
=========================

# Table of Contents

- [Customize the cursor encoder of the edges of a connection](#customize-the-cursor-encoder-of-the-edges-of-a-connection)
- [Change arguments of `TypeGenerator`](#change-arguments-of-typegenerator-class)
- [Add magic `__get` method to `ArgumentInterface` implementors](#add-magic-__get-method-to-argumentinterface-implementors)
- [Annotations - Flattened annotations](#annotations---flattened-annotations)
- [Annotations - Attributes changed](#annotations---attributes-changed)
- [Rename `GlobalVariables` to `GraphQLServices`](#rename-globalvariables-to-graphqlservices)
- [Replace `overblog_graphql.global_variable` tag](#replace-overblog_graphqlglobal_variable-tag)
- [Replace `resolver` expression function](#replace-resolver-expression-function)
- [Rename `ResolverInterface` to `QueryInterface`](#rename-resolverinterface-to-queryinterface)
- [Remove Argument deprecated method](#remove-argument-deprecated-method)
- [Remove ConnectionBuilder deprecated class](#remove-connectionbuilder-deprecated-class)
- [Remove XML type configuration](#remove-xml-type-configuration-support)

### Customize the cursor encoder of the edges of a connection

The connection builder now accepts an optional custom cursor encoder as first argument of the constructor.

```diff
$connectionBuilder = new ConnectionBuilder(
+   new class implements CursorEncoderInterface {
+       public function encode($value): string
+       {
+           ...
+       }
+
+       public function decode(string $cursor)
+       {
+           ...
+       }
+   }
    static function (iterable $edges, PageInfoInterface $pageInfo) {
        ...
    },
    static function (string $cursor, $value, int $index) {
        ...
    }
);
```

### Change arguments of `TypeGenerator` class

The `Overblog\GraphQLBundle\Generator\TypeGenerator` service is used internally for compilation of GraphQL types. If you 
overrode the service definition, please take into account the new constructor signature:

```php
public function __construct(
   array $typeConfigs,
   TypeBuilder $typeBuilder,
   EventDispatcherInterface $eventDispatcher,
   TypeGeneratorOptions $options
)
```
`TypeBuilder` here is a new service `Overblog\GraphQLBundle\Generator\TypeBuilder`, which is also used internally.
The rest of the arguments were moved into the separate class `Overblog\GraphQLBundle\Generator\TypeGeneratorOptions` 
with the following constructor signature:

```php
public function __construct(
    string $namespace,
    ?string $cacheDir,
    bool $useClassMap = true,
    ?string $cacheBaseDir = null,
    ?int $cacheDirMask = null
)
```
### Add magic `__get` method to `ArgumentInterface` implementors

The interface `Overblog\GraphQLBundle\Definition\ArgumentInterface` as well as implementing it class 
`Overblog\GraphQLBundle\Definition\Argument` now have the magic `__get` method:

```diff
interface ArgumentInterface extends ArrayAccess, Countable
{
    /**
     * @return array the old array
     */
    public function exchangeArray(array $array): array;

    public function getArrayCopy(): array;

+   /**
+    * @return mixed
+    */
+   public function __get(string $name);
}

class Argument implements ArgumentInterface
{
    // ...

+   public function __get(string $name)
+   {
+       return $this->rawArguments[$name] ?? null;
+   }
}
```
If you use your own class for resolver arguments, then it should have a `__get` method as well.


### Annotations - Flattened annotations

In order to prepare to PHP 8 attributes (they don't support nested attributes at the moment. @see https://github.com/symfony/symfony/issues/38503), the following annotations have been flattened: `@FieldsBuilder`, `@FieldBuilder`, `@ArgsBuilder`, `@Arg` and `@EnumValue`. 

Before:
```php
/**
 * @GQL\Type
 */
class MyType {
    /**
     * @GQL\Field(args={
     *   @GQL\Arg(name="arg1", type="String"),
     *   @GQL\Arg(name="arg2", type="Int")
     * })
     */
    public function myFields(?string $arg1, ?int $arg2) {..}
}

```

After:
```php
/**
 * @GQL\Type
 */
class MyType {
    /**
     * @GQL\Field
     * @GQL\Arg(name="arg1", type="String"),
     * @GQL\Arg(name="arg2", type="Int")
     */
    public function myFields(?string $arg1, ?int $arg2) {..}
}

```

### Annotations - Attributes changed

Change the attributes name of `@FieldsBuilder` annotation from `builder` and `builderConfig` to `value` and `config`. 

Before:
```php
/**
 * @GQL\Type(name="MyType", builders={@GQL\FieldsBuilder(builder="Timestamped", builderConfig={opt1: "val1"})})
 */
class MyType {

}
```

After:
```php
/**
 * @GQL\Type("MyType")
 * @GQL\FieldsBuilder(value="Timestamped", config={opt1: "val1"})
 */
class MyType {

}
```

### Rename `GlobalVariables` to `GraphQLServices`

The `GlobalVariables` class was renamed into `GraphQLServices` to better reflect its purpose - holding services,
passed to all generated GraphQL types.


### Replace `overblog_graphql.global_variable` tag

If you have any services tagged with `overblog_graphql.global_variable`, they should now be tagged with
`overblog_graphql.service` instead.


### Replace `resolver` expression function

The signature of the `resolver` expression function has been changed.

Old signature (deprecated): <code><b>resolver</b>(string <b>$alias</b>, array <b>$args</b> = []): mixed</code>  
New signature: <code><b>query</b>(string <b>$alias</b>, <b>...$args</b>): mixed</code>

Example:
```diff
- resolver('get_posts', [args, info, value])
+ query('get_posts', args, info, value)
```


### Rename `ResolverInterface` to `QueryInterface`

The `Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface` interface is deprecated. Use
`Overblog\GraphQLBundle\Definition\Resolver\QueryInterface` instead.

Example:
```diff
- use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
+ use Overblog\GraphQLBundle\Definition\Resolver\QueryInterface;

- class UserResolver implements ResolverInterface
+ class UserQuery implements QueryInterface
{
    // ...
}
```

### Remove Argument deprecated method

Method `Overblog\GraphQLBundle\Definition\Argument::getRawArguments` is replaced by
`Overblog\GraphQLBundle\Definition\Argument::getArrayCopy`.

### Remove ConnectionBuilder deprecated class

Class `Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder` is replaced by
`Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder`.

### Remove XML type configuration support

XML type configuration is no longer supported.

UPGRADE FROM 0.12 to 0.13
=======================

# Table of Contents

- [Rename default_field config](#rename-default_field-config)
- [Improve default field resolver](#improve-default-field-resolver)
- [Use service tags to register resolver maps](#use-service-tags-to-register-resolver-maps)

### Rename default_field config

```diff
overblog_graphql:
    definitions:
-       default_resolver: ~
+       default_field_resolver: ~
```

The new `default_field_resolver` config entry accepts callable service id.

### Improve default field resolver

Stop using internally `symfony/property-access` package
since it was a bottleneck to performance for large schema.

Array access and camelize getter/isser are supported but hasser,
jQuery style (e.g. `last()`) and "can" property accessors
are no more supported out-of-the-box,
please implement a custom resolver if these accessors are needed.

Globally:

```yaml
overblog_graphql:
    definitions:
        default_field_resolver: 'App\GraphQL\CustomResolver'
```

[see default field resolver for more details](https://webonyx.github.io/graphql-php/data-fetching/#default-field-resolver)

Per Type:

```yaml
MyType:
    type: object
    config:
        resolveField: 'App\GraphQL\MyTypeResolver::defaultFieldResolver'
        fields:
            name: {type: String}
            email: {type: String}
```

[see default Field Resolver per type for more details](https://webonyx.github.io/graphql-php/data-fetching/#default-field-resolver-per-type)

### Use service tags to register resolver maps

The resolver maps used to be configured using the `overblog_graphql.definitions.schema.resolver_maps`
option. This has been deprecated in favour of using service tags to register them.

```diff
# config/graphql.yaml
overblog_graphql:
    definitions:
        schema:
             # ...
-            resolver_maps:
-                - 'App\GraphQL\MyResolverMap'
```

```diff
# services/graphql.yaml
services:
-    App\GraphQL\MyResolverMap: ~
+    App\GraphQL\MyResolverMap:
+        tags:
+            - { name: overblog_graphql.resolver_map, schema: default }
```


UPGRADE FROM 0.11 to 0.12
=======================

# Table of Contents

- [Remove auto mapping configuration](#remove-auto-mapping-configuration)
- [Relay Paginator, Connections & Edges](#relay-paginator-connections--edges)
- [Remove obsoletes deprecations](#remove-obsoletes-deprecations)
- [Simplify executor interface](#simplify-executor-interface)

### Remove auto mapping configuration

* The AutoMapping configuration entry has been removed in favor of Symfony 4+ service configuration.

Upgrading:
- Delete old configuration.
     ```diff
     overblog_graphql:
         definitions:
     -        auto_mapping: ~
     ```
- use Symfony 4+ service configuration to tag your types, resolvers or mutation.
    ```yaml
    # config/services.yaml
    services:
        _defaults:
            autoconfigure: true

        App\GraphQL\:
            resource: ../src/GraphQL
    ```


### Relay Paginator, Connections & Edges

-   Following the [paginator update](docs/helpers/relay-paginator.md) and the use of interfaces for Relay Connection & Edge, getters & setters must be use to manipulate Connection, Edge and PageInfo Properties

Before :

```php
$connection->edges = $edges;
$connection->totalCount = 10;
...
$edge->cursor = $cursor;
$edge->node = $node;

```

After :

```php
$connection->setEdges($edges);
$connection->setTotalCount(10);
...
$edge->setCursor($cursor);
$edge->setNode($node);
```

Connection builder has been moved and it methods are no more accessible statically:

Before:

```php
use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;

ConnectionBuilder::connectionFromArray([]);
```

After:

```php
use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder;

$connectionBuilder = new ConnectionBuilder();
$connectionBuilder->connectionFromArray([]);
```

### Remove obsoletes deprecations

The builder short syntax (Field: Builder => Field: {builder: Builder}) is obsolete:

```diff
Foo:
    type: object
    config:
        fields:
-            bar: MyBuilder
+            bar: {builder: MyBuilder}

```

Relay builder without 'Relay::' prefix is obsolete:

```diff
Foo:
    type: object
    config:
        fields:
            bar:
-                argsBuilder: ConnectionArgs
+                argsBuilder: "Relay::Connection"
```

### Simplify executor interface

This section is only for users using custom executor.

The interface move to be look a little be more to `GraphQL\GraphQL`
`promiseToExecute` method.

In `Overblog\GraphQLBundle\Executor\ExecutorInterface`
`setPromiseAdapter` and `setDefaultFieldResolver` has been removed.

Promise adapter is now the first argument (`$promiseAdapter`)
and default field resolver the 7th argument (`$fieldResolver`) of
`Overblog\GraphQLBundle\Executor\ExecutorInterface::execute` method.


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

When using these functionalities, type will be accessible only by FQCN in schema definition
(if class doesn't implement `Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface`).
So if you want to use the `true` type name don't forget to declare it as an alias using interface.
This change is to increase performance, types are lazy-loaded.

Here's an example:

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

Here is how this can be done in 0.11:

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

**Since 0.11** Non detect types should be explicitly declared

Here is a concrete example:
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
In above example `Baz` and `Bar` can not be detected by graphql-php during static schema analysis,
an `GraphQL\Error\InvariantViolation` exception will be thrown with the following message:
   ```text
   Could not find possible implementing types for FooInterface in schema.
   Check that schema.types is defined and is an array of all possible types in the schema.
   ```
Here is how this can be fixed:

   ```yaml
   overblog_graphql:
      definitions:
         schema:
            query: Query
            types: [Bar, Baz]
   ```

### Events

`Overblog\GraphQLBundle\Event\ExecutorContextEvent::setExecutorContext` method has been removed as `context`
is now an `ArrayObject`. When using `graphql.executor.context` listener the value will now be accessible only
in `context` variables and not in `rootValue`. `context` and `rootValue` have been separated, if you need to
use `rootValue` see [event documentation for more details](Resources/doc/events/index.md).

**Before 0.11**
`context` and `rootValue` were of type `array` with same value so `$context === $info->rootValue` and
`$context === $value` in root query resolver. Because of this, uploaded files was accessible in
`$context['request_files']` and `$info->rootValue['request_files']`.

**Since 0.11**
`context` is of type `ArrayObject` and `rootValue` has no typeHint (default: `null`) so
`$context !== $info->rootValue` and `$context !== $value` in root query resolver.
Uploaded files is no longer accessible under `$info->rootValue['request_files']` out of the box.

### Change fluent resolvers id

The use of class name as prefix of fluent resolver id removes the possibility to use same class as 2 different services.
See issue [#296](https://github.com/overblog/GraphQLBundle/issues/296) for more detail
Because of this, in v0.11 we are using service id as prefix (like in Symfony 4.1)...

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


UPGRADE FROM 0.9 to 0.10
=======================

# Table of Contents

- [Symfony](#symfony)

### Symfony

* Minimal supported Symfony version is now `^3.1 || ^4.0`

  We've dropped support for Symfony 2.8 and 3.0

Upgrading your Symfony version:
- [Upgrading to Symfony 3.0](https://github.com/symfony/symfony/blob/master/UPGRADE-3.0.md)
- [Upgrading to Symfony 3.1](https://github.com/symfony/symfony/blob/master/UPGRADE-3.1.md)

