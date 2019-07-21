Type System
============

Types
-----

Types can be define 3 different ways:

1. **The configuration way**

    Creating this file extension **.types.yml** or **.types.xml**
    in `src/*Bundle/Resources/config/graphql` or `app/config/graphql`.
    See the different possible types:
    * [Scalars](scalars.md)
    * [Object](object.md)
    * [Interface](interface.md)
    * [Union](union.md)
    * [Enum](enum.md)
    * [Input Object](input-object.md)
    * [Lists](lists.md)
    * [Non-Null](non-null.md)

    You can also define custom dirs using config:
    ```yaml
    overblog_graphql:
        definitions:
            mappings:
                # auto_discover: false # to disable bundles and root dir auto discover
                types:
                    -
                        type: yaml # or xml or graphql or annotation null
                        dir: "%kernel.root_dir%/.../mapping" # sub directories are also searched
                        # suffix: .types # use to change default file suffix
                    -
                        types: [yaml, graphql] # to include different types from the same dir
                        dir: "%kernel.root_dir%/.../mapping"
    ```

2. **The PHP way**

    You can also declare PHP types (any subclass of `GraphQL\Type\Definition\Type`) 
    in `src/*Bundle/GraphQL` or `app/GraphQL`
    they will be auto discover (thanks to auto mapping). Auto map classes are accessible by service id
    (example: `AppBunble\GraphQL\Type\DateTimeType`), you can also alias a type by
    implementing `Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface`
    that returns an array of aliases.

    here an example:

    ```php
    <?php

    namespace App\GraphQL\Type;

    use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
    use GraphQL\Type\Definition\ScalarType;

    class DateTimeType extends ScalarType implements AliasedInterface
    {
        /**
         * {@inheritdoc}
         */
        public static function getAliases(): array
        {
            return ['DateTime', 'Date'];
        }
        // ...
    }
    ```

    Here an example of how this can be done with DI `autoconfigure`:

    ```yaml
    services:
        _defaults:
            autoconfigure: true

        App\Type\:
            resource: '../src/Type'
    ```

    **Note:**
    * Types are lazy loaded so when using Symfony DI `autoconfigure` or this bundle auto mapping, the
    only access to type is FQCN (or aliases if implements the aliases interface).
    * When using service id as FQCN in yaml definition, backslashes must be correctly escaped,

3. **The service way**

    Creating a service tagged `overblog_graphql.type`
    ```yaml
    services:
        AppBundle\GraphQL\Type\DateTime:
            # only for sf < 3.3
            #class: AppBundle\GraphQL\Type\DateTime
            tags:
                - { name: overblog_graphql.type, alias: DateTime }
    ```
