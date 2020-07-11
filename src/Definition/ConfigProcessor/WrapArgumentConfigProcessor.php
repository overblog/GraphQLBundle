<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use Overblog\GraphQLBundle\Definition\ArgumentFactory;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use function is_array;
use function is_callable;

final class WrapArgumentConfigProcessor implements ConfigProcessorInterface
{
    private ArgumentFactory $argumentFactory;

    public function __construct(ArgumentFactory $argumentFactory)
    {
        $this->argumentFactory = $argumentFactory;
    }

    public function process(LazyConfig $lazyConfig): LazyConfig
    {
        $lazyConfig->addPostLoader(function ($config) {
            if (isset($config['resolveField']) && is_callable($config['resolveField'])) {
                $config['resolveField'] = $this->argumentFactory->wrapResolverArgs($config['resolveField']);
            }

            if (isset($config['fields'])) {
                $config['fields'] = function () use ($config) {
                    $fields = $config['fields'];
                    if (is_callable($config['fields'])) {
                        $fields = $config['fields']();
                    }

                    return $this->wrapFieldsArgument($fields);
                };
            }

            return $config;
        });

        return $lazyConfig;
    }

    private function wrapFieldsArgument(array $fields): array
    {
        foreach ($fields as &$field) {
            if (is_array($field) && isset($field['resolve']) && is_callable($field['resolve'])) {
                $field['resolve'] = $this->argumentFactory->wrapResolverArgs($field['resolve']);
            }
        }

        return $fields;
    }
}
