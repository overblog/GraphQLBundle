<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Builder\FormDescriber;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Resolver\AccessResolver;

final class FormConfigProcessor implements ConfigProcessorInterface
{
    /** @var FormDescriber */
    private $describer;

    /** @var callable */
    private $defaultResolver;

    public function __construct(FormDescriber $describer)
    {
        $this->describer = $describer;
    }

    public static function transformArgs(array $fields, FormDescriber $describer)
    {
        dump($fields);
        foreach ($fields as &$field) {
            if (isset($field['form'])) {
                //dump($field['argsForm']);
                $args = $describer->describe($field['form']);
                unset($field['form']);                
                $field['args'] = isset($field['args']) && \is_array($field['args']) ? \array_merge($args, $field['args']) : $args;
            }
        }

        return $fields;
    }

    public function process(LazyConfig $lazyConfig): LazyConfig
    {
        $lazyConfig->addPostLoader(function ($config) {
            dump($config);
            if (isset($config['fields']) && \is_callable($config['fields'])) {
                $config['fields'] = function () use ($config) {
                    $fields = $config['fields']();

                    return static::transformArgs($fields, $this->describer);
                };
            }

            return $config;
        });

        return $lazyConfig;
    }
}
