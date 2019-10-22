Types configuration callback
============================

This callback is called **only** when building the cache in order to alter the built types configuration.

The callback must implement the `TypesConfigurationCallbackInterface`. It **must be** a standalone class, not a service. Specify the callback using `types_callback` configuration:

```yaml
overblog_graphql:
    definitions:
        mappings:
            types_callback: "App\\GraphQL\\Type\\TypesConfigurationCallback"
            types:
                ...
```

Here's an example to create a custom `status_enum` type with `enabled`, `disabled` and `removed` values.

In the example, the `StatusEnum` field is converted to an `enum` with  `removed` value added dynamically using `allow_remove` flag:

```yaml
StatusEnum:
    type: status_enum
    allow_remove: true
```

```php
<?php

namespace App\GraphQL\Type;

use Overblog\GraphQLBundle\DependencyInjection\TypesConfigurationCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TypesConfigurationCallback implements TypesConfigurationCallbackInterface
{
    public static function processTypesConfiguration(array $typesConfiguration, ContainerBuilder $container, array $config): array
    {
        foreach ($typesConfiguration as $type => &$configuration) {
            $fieldType = $configuration['type'] ?? '';
            if ('status_enum' !== $fieldType) {
                continue;
            }

            $allowRemove = $configuration['allow_remove'] ?? false;
     
            // Remove extra configuration and set "enum" type.
            unset($configuration['allow_remove']);
            $configuration['type'] = 'enum';

            // Build enum values.
            $configuration['config']['values'] = [
                'enabled' => ['value' => 'enabled', 'description' => 'Field is enabled'],
                'disabled' => ['description' => 'Field is disabled'],
            ];

            if ($allowRemove) {
                $configuration['config']['values']['removed'] = ['description' => 'Field is removed'];
            }
        }

        return $typesConfiguration;
    }
}
```
