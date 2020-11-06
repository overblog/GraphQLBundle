# Resolver

To ease development we named 2 types of resolver:

- `Resolver` that should be use for resolving readonly actions (query)
- `Mutation` that should be use for resolving writing actions (mutation)

This is just a recommendation.

Resolvers can be define 2 different ways:

## The PHP way


You can declare a resolver (any class that implements `Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface` or `Overblog\GraphQLBundle\Definition\Resolver\MutationInterface`) in `src/*Bundle/GraphQL` or `app/GraphQL` and they will be auto discovered.
Auto map classes method are accessible by:
* double-colon (::) to separate service id (class name) and the method names
(example: `AppBunble\GraphQL\CustomResolver::myMethod`)
* for callable classes you can use the service id (example: `AppBunble\GraphQL\InvokeResolver` for a resolver implementing the `__invoke` method) you can also alias a type by implementing `Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface` which returns a map of method/alias. The service created will autowire the `__construct` and `Symfony\Component\DependencyInjection\ContainerAwareInterface::setContainer` methods.

**Note:**
* When using service id as FQCN in yaml or annotation definition, backslashes must be correctly escaped, here an example:
`'@=resolver("App\\GraphQL\\Resolver\\Greetings", [args["name"]])'`.
* You can also see the more straight forward way using [resolver map](resolver-map.md).

### Resolver

Example using an alias:
````yaml
resolve: '@=resolver("say_hello", [args["name"]])'
````

```php
<?php
# src/GraphQL/Resolver/Greetings.php
namespace App\GraphQL\Resolver;

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

class Greetings implements ResolverInterface, AliasedInterface
{
    public function sayHello($name)
    {
        return sprintf('hello %s!!!', $name);
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return ['sayHello' => 'say_hello'];
    }
}
````

Example using a fully qualified method name:
````yaml
resolve: '@=resolver("App\\GraphQL\\Resolver\\Greetings::sayHello", [args["name"]])'
````

Note: backslashes must be correctly escaped and respect the use of single and double quotes.

```php
<?php
# src/GraphQL/Resolver/Greetings.php
namespace App\GraphQL\Resolver;

use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

class Greetings implements ResolverInterface
{
    public function sayHello($name)
    {
        return sprintf('hello %s!!!', $name);
    }
}
```

Example using the class invoker:
````yaml
resolve: '@=resolver("App\\GraphQL\\Resolver\\Greetings", [args["name"]])'
````

```php
<?php
# src/GraphQL/Resolver/Greetings.php
namespace App\GraphQL\Resolver;

use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

class Greetings implements ResolverInterface
{
    public function __invoke($name)
    {
        return sprintf('hello %s!!!', $name);
    }
}
```
This way `SayHello` resolver can be accessed with `App\GraphQL\Resolver\Greetings`.

You may also use the invoker to define a type-wide resolver with the `resolveField` option:

````yaml
# config/graphql/types/MyType.types.yaml
MyType:
    type: object
    config:
        resolveField: '@=resolver("App\\GraphQL\\Resolver\\Greetings", [info, args["name"]])'
        fields:
            hello:
                type: String
            goodbye:
                type: String
````

```php
<?php
# src/GraphQL/Resolver/Greetings.php
namespace App\GraphQL\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

class Greetings implements ResolverInterface
{
    public function __invoke(ResolveInfo $info, $name)
    {
        if($info->fieldName === 'hello'){
            return sprintf('hello %s!!!', $name);
        }
        else if($info->fieldName === 'goodbye'){
            return sprintf('goodbye %s!!!', $name);
        }
        else{
            throw new \DomainException('Unknown greetings');
        }
    }
}
```

### Mutation

```php
<?php
# src/GraphQL/Mutation/CalcMutation.php
namespace App\GraphQL\Mutation;

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;

class CalcMutation implements MutationInterface, AliasedInterface
{
    private $value;

    public function addition($number)
    {
        $this->value += $number;
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return ['addition' => 'add'];
    }
}
```
`addition` mutation can be access by using `App\GraphQL\Mutation\CalcMutation::addition` or
`add` alias.

Here an example of how this can be done with DI `autoconfigure`:

```yaml
services:
    _defaults:
        autoconfigure: true

    Overblog\GraphQLBundle\GraphQL\Relay\:
        resource: ../../GraphQL/Relay/{Mutation,Node}
```

## The service way

Creating a service tagged `overblog_graphql.resolver` for resolvers
or `overblog_graphql.mutation` for mutations.

Using the php way examples:

```yaml
services:
    App\GraphQL\Resolver\Greetings:
        # only for sf < 3.3
        #class: App\GraphQL\Resolver\Greetings
        tags:
            - { name: overblog_graphql.resolver, method: sayHello, alias: say_hello } # add alias say_hello
            - { name: overblog_graphql.resolver, method: sayHello } # add service id "App\GraphQL\Resolver\Greetings"
```

`SayHello` resolver can be access by using `App\GraphQL\Resolver\Greetings::sayHello` or
`say_hello` alias.

for invokable classes no need to use `alias` and `method` attributes:

```yaml
services:
    App\GraphQL\Resolver\Greetings:
        # only for sf < 3.3
        #class: App\GraphQL\Resolver\Greetings
        tags:
            - { name: overblog_graphql.resolver }
```

This way resolver can be accessed with service id `App\GraphQL\Resolver\Greetings`.

for mutation:

```yaml
services:
    App\GraphQL\Mutation\CalcMutation:
        # only for sf < 3.3
        #class: App\GraphQL\Mutation\CalcMutation
        tags:
            - { name: overblog_graphql.mutation, method: addition, alias: add }
```
`addition` mutation can be access by using `App\GraphQL\Mutation\CalcMutation::addition` or
`add` alias.

### Default field resolver

The default field resolver can be define using config:

```yaml
overblog_graphql:
    definitions:
       default_field_resolver: 'my_default_field_resolver_service'
```

Default field resolver should be a callable service (implementing `__invoke` method)

Next step [solving N+1 problem](solving-n-plus-1-problem.md)
