# Resolver

To ease development we named 2 types of resolver:

- `Resolver` that should be use for resolving readonly actions (query)
- `Mutation` that should be use for resolving writing actions (mutation)

This is just a recommendation.

Resolvers can be define 2 different ways

1. **The PHP way**

    You can declare a resolver (any class that implements `Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface`
    or `Overblog\GraphQLBundle\Definition\Resolver\MutationInterface`)
    in `src/*Bundle/GraphQL` or `app/GraphQL` and they will be auto discovered.
    Auto map classes method are accessible by:
    * the class method name (example: `AppBunble\GraphQL\CustomResolver::myMethod`)
    * the FQCN for callable classes (example: `AppBunble\GraphQL\InvokeResolver` for a resolver implementing the `__invoke` method)
    you can also alias a type by implementing `Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface`
    which returns a map of method/alias. The service created will autowire the `__construct`
    and `Symfony\Component\DependencyInjection\ContainerAwareInterface::setContainer` methods.

    Here an example:
    ```php
    <?php

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
        public static function getAliases()
        {
            return ['sayHello' => 'say_hello'];
        }
    }
    ```

    `SayHello` resolver can be access by using `App\GraphQL\Resolver\Greetings::sayHello` or
    `say_hello` alias.

    we can also use class invoker:
    ```php
    <?php

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

    for mutation:

    ```php
    <?php

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
        public static function getAliases()
        {
            return ['addition' => 'add'];
        }
    }
    ```
    `addition` mutation can be access by using `App\GraphQL\Mutation\CalcMutation::addition` or
    `add` alias.

    You can also define custom dirs using the config:
    ```yaml
    overblog_graphql:
        definitions:
            auto_mapping:
                directories:
                    - "%kernel.root_dir%/src/*Bundle/CustomDir"
                    - "%kernel.root_dir%/src/AppBundle/{foo,bar}"
    ```

    If using Symfony 3.3+ disabling auto mapping can be a solution to leave place to native
    DI `autoconfigure`:

    ```yaml
    overblog_graphql:
        definitions:
            auto_mapping: false
    ```

    Here an example of how this can be done with DI `autoconfigure`:

    ```yaml
    App\Mutation\:
        resource: '../src/Mutation'
        tags: ['overblog_graphql.mutation']

    App\Resolver\:
        resource: '../src/Resolver'
        tags: ['overblog_graphql.resolver']

    App\Type\:
        resource: '../src/Type'
        tags: ['overblog_graphql.type']
    ```

    **Note:**
    * When using FQCN in yaml definition, backslash must be correctly quotes,
      here an example `'@=resolver("App\\GraphQL\\Resolver\\Greetings", [args['name']])'`.

2. **The service way**

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
                - { name: overblog_graphql.resolver, method: sayHello } # add method full qualified name
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

    This way resolver can be accessed with FQCN `App\GraphQL\Resolver\Greetings`.

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

Next step [solving N+1 problem](solving-n-plus-1-problem.md)
