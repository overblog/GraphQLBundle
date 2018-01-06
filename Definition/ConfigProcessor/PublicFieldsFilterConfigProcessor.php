<?php

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use Overblog\GraphQLBundle\Definition\LazyConfig;

final class PublicFieldsFilterConfigProcessor implements ConfigProcessorInterface
{
    public static function filter(array $fields)
    {
        return array_filter(
            $fields,
            function ($field, $fieldName) {
                $exposed = true;

                if (isset($field['public'])) {
                    if (is_callable($field['public'])) {
                        $exposed = call_user_func($field['public'], $fieldName);
                    } else {
                        $exposed = (bool) $field['public'];
                    }
                }

                return $exposed;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(LazyConfig $lazyConfig)
    {
        $configLoader = $lazyConfig->getLoader();

        $lazyConfig->setLoader(function (...$args) use ($configLoader) {
            $config = $configLoader(...$args);

            if (isset($config['fields']) && is_callable($config['fields'])) {
                $config['fields'] = function () use ($config) {
                    $fields = $config['fields']();

                    return static::filter($fields);
                };
            }

            return $config;
        });

        return $lazyConfig;
    }
}
