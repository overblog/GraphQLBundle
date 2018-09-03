<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Resolver\AccessResolver;

final class AclConfigProcessor implements ConfigProcessorInterface
{
    /** @var AccessResolver */
    private $accessResolver;

    /** @var callable */
    private $defaultResolver;

    public function __construct(AccessResolver $accessResolver, callable $defaultResolver)
    {
        $this->accessResolver = $accessResolver;
        $this->defaultResolver = $defaultResolver;
    }

    public static function acl(array $fields, AccessResolver $accessResolver, callable $defaultResolver)
    {
        $deniedAccess = static function (): void {
            throw new UserWarning('Access denied to this field.');
        };
        foreach ($fields as &$field) {
            if (isset($field['access']) && true !== $field['access']) {
                $accessChecker = $field['access'];
                if (false === $accessChecker) {
                    $field['resolve'] = $deniedAccess;
                } elseif (\is_callable($accessChecker)) {
                    $field['resolve'] = function ($value, $args, $context, ResolveInfo $info) use ($field, $accessChecker, $accessResolver, $defaultResolver) {
                        $resolverCallback = self::findFieldResolver($field, $info, $defaultResolver);
                        $isMutation = 'mutation' === $info->operation->operation && $info->parentType === $info->schema->getMutationType();

                        return $accessResolver->resolve($accessChecker, $resolverCallback, [$value, $args, $context, $info], $isMutation);
                    };
                }
            }
        }

        return $fields;
    }

    public function process(LazyConfig $lazyConfig): LazyConfig
    {
        $lazyConfig->addPostLoader(function ($config) {
            if (isset($config['fields']) && \is_callable($config['fields'])) {
                $config['fields'] = function () use ($config) {
                    $fields = $config['fields']();

                    return static::acl($fields, $this->accessResolver, $this->defaultResolver);
                };
            }

            return $config;
        });

        return $lazyConfig;
    }

    /**
     * @param array       $field
     * @param ResolveInfo $info
     * @param callable    $defaultResolver
     *
     * @return callable
     */
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
