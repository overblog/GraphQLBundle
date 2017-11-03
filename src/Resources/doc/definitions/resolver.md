# Resolver

To ease developments we names 2 types of resolver:

- `Resolver` that should be use for resolving readonly actions (query)
- `Mutation` that should be use for resolving writing actions (mutation)

This is just a recommendation.

Resolvers can be define 2 different ways

1. **The PHP way**

    You can declare resolver (any class that implements `Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface`
    or `Overblog\GraphQLBundle\Definition\Resolver\MutationInterface`)
    in `src/*Bundle/GraphQL` or `app/GraphQL` they will be auto discover.
    Auto map classes method are accessible by:
    * the class method name (example: `AppBunble\GraphQL\CustomResolver::myMethod`)
    * the FQCN for callable classes (example: `AppBunble\GraphQL\InvokeResolver` for resolver implementing `__invoke` method)
    you can also alias type implementing `Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface`
    that returns a map of method/alias. The service created will autowire `__construct`
    and `Symfony\Component\DependencyInjection\ContainerAwareInterface::setContainer` methods.
    You can also define custom dirs using config:
    ```yaml
    overblog_graphql:
        definitions:
            auto_mapping:
                directories:
                    - "%kernel.root_dir%/src/*Bundle/CustomDir"
                    - "%kernel.root_dir%/src/AppBundle/{foo,bar}"
    ```
    To disable auto mapping:
    ```yaml
    overblog_graphql:
        definitions:
            auto_mapping: false
    ```

2. **The service way**

    Creating a service tagged `overblog_graphql.resolver` for resolvers
    or `overblog_graphql.mutation` for mutations.

    ```yaml
    services:
        AppBunble\GraphQL\CustomResolver:
            # only for sf < 3.3
            #class: AppBunble\GraphQL\CustomResolver
            tags:
                - { name: overblog_graphql.resolver, method: add }
    ```
