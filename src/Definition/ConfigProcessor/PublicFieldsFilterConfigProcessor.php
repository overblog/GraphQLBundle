<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use function array_filter;
use function is_array;
use function is_callable;
use const ARRAY_FILTER_USE_BOTH;

final class PublicFieldsFilterConfigProcessor implements ConfigProcessorInterface
{
    public static function filter(array $fields): array
    {
        return array_filter(
            $fields,
            function ($field, $fieldName) {
                $exposed = true;

                if (is_array($field) && isset($field['public']) && is_callable($field['public'])) {
                    $exposed = (bool) $field['public']($fieldName);
                }

                return $exposed;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function process(array $config): array
    {
        if (isset($config['fields']) && is_callable($config['fields'])) {
            $config['fields'] = function () use ($config) {
                $fields = $config['fields']();

                return static::filter($fields);
            };
        }

        return $config;
    }
}
