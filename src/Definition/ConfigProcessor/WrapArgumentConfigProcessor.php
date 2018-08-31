<?php

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Resolver\Resolver;

final class WrapArgumentConfigProcessor implements ConfigProcessorInterface
{
    public function process(LazyConfig $lazyConfig)
    {
        $lazyConfig->addPostLoader(function ($config) {
            if (isset($config['resolveField']) && \is_callable($config['resolveField'])) {
                $config['resolveField'] = Resolver::wrapArgs($config['resolveField']);
            }

            if (isset($config['fields'])) {
                $config['fields'] = function () use ($config) {
                    $fields = $config['fields'];
                    if (\is_callable($config['fields'])) {
                        $fields = $config['fields']();
                    }

                    return self::wrapFieldsArgument($fields);
                };
            }

            return $config;
        });

        return $lazyConfig;
    }

    private static function wrapFieldsArgument(array $fields)
    {
        foreach ($fields as &$field) {
            if (isset($field['resolve']) && \is_callable($field['resolve'])) {
                $field['resolve'] = Resolver::wrapArgs($field['resolve']);
            }
        }

        return $fields;
    }
}
