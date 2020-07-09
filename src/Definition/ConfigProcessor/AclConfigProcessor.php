<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Resolver\AccessResolver;
use function is_array;
use function is_callable;

final class AclConfigProcessor implements ConfigProcessorInterface
{
    private AccessResolver $accessResolver;

    /** @var callable */
    private $defaultResolver;

    public function __construct(AccessResolver $accessResolver, callable $defaultResolver)
    {
        $this->accessResolver = $accessResolver;
        $this->defaultResolver = $defaultResolver;
    }

    public static function acl(array $fields, AccessResolver $accessResolver, callable $defaultResolver): array
    {
        $deniedAccess = static function (): void {
            throw new UserWarning('Access denied to this field.');
        };
        foreach ($fields as &$field) {
            if (is_array($field) && isset($field['access']) && true !== $field['access']) {
                $accessChecker = $field['access'];
                if (false === $accessChecker) {
                    $field['resolve'] = $deniedAccess;
                } elseif (is_callable($accessChecker)) {
                    $field['resolve'] = function ($value, $args, $context, ResolveInfo $info) use ($field, $accessChecker, $accessResolver, $defaultResolver) {
                        $resolverCallback = self::findFieldResolver($field, $info, $defaultResolver);

                        return $accessResolver->resolve($accessChecker, $resolverCallback, [$value, $args, $context, $info], $field['useStrictAccess'] ?? true);
                    };
                }
            }
        }

        return $fields;
    }

    public function process(LazyConfig $lazyConfig): LazyConfig
    {
        $lazyConfig->addPostLoader(function ($config) {
            if (isset($config['fields']) && is_callable($config['fields'])) {
                $config['fields'] = function () use ($config) {
                    $fields = $config['fields']();

                    return static::acl($fields, $this->accessResolver, $this->defaultResolver);
                };
            }

            return $config;
        });

        return $lazyConfig;
    }

    private static function findFieldResolver(array $field, ResolveInfo $info, callable $defaultResolver): callable
    {
        if (isset($field['resolve'])) {
            $resolver = $field['resolve'];
        } elseif (isset($info->parentType->config['resolveField'])) {
            $resolver = $info->parentType->config['resolveField'];
        } else {
            $resolver = $defaultResolver;
        }

        return $resolver;
    }
}
