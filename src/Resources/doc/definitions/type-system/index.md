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
                        type: yaml # or xml or null
                        dir: "%kernel.root_dir%/.../mapping" # sub directories are also searched
                        # suffix: .types # use to change default file suffix
    ```

2. **The PHP way**

    You can also declare PHP types (any subclass of `GraphQL\Type\Definition\Type`) 
    in `src/*Bundle/GraphQL` or `app/GraphQL`
    they will be auto discover (thanks to auto mapping). Auto map classes are accessible by FQCN
    (example: `AppBunble\GraphQL\Type\DateTimeType`), you can also alias type adding
    a public static function `getAliases`
    that returns an array of aliases.
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
