<?php

namespace Overblog\GraphQLBundle\Definition\ConfigProcessor;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Resolver\AccessResolver;

final class AclConfigProcessor implements ConfigProcessorInterface
{
    /** @var AccessResolver */
    private $accessResolver;

    public function __construct(AccessResolver $accessResolver)
    {
        $this->accessResolver = $accessResolver;
    }

    public static function acl(array $fields, AccessResolver $accessResolver)
    {
        $deniedAccess = static function () {
            throw new UserWarning('Access denied to this field.');
        };
        foreach ($fields as &$field) {
            if (isset($field['access']) && true !== $field['access']) {
                $accessChecker = $field['access'];
                if (false === $accessChecker) {
                    $field['resolve'] = $deniedAccess;
                } elseif (is_callable($accessChecker) && isset($field['resolve'])) { // todo manage when resolver is not set
                    $field['resolve'] = static function ($value, $args, $context, ResolveInfo $info) use ($field, $accessChecker, $accessResolver) {
                        $resolverCallback = $field['resolve'];
                        $isMutation = 'mutation' === $info->operation->operation && $info->parentType === $info->schema->getMutationType();

                        return $accessResolver->resolve($accessChecker, $resolverCallback, [$value, new Argument($args), $context, $info], $isMutation);
                    };
                }
            }
        }

        return $fields;
    }

    public function process(LazyConfig $lazyConfig)
    {
        $lazyConfig->addPostLoader(function ($config) {
            if (isset($config['fields']) && is_callable($config['fields'])) {
                $config['fields'] = function () use ($config) {
                    $fields = $config['fields']();

                    return static::acl($fields, $this->accessResolver);
                };
            }

            return $config;
        });

        return $lazyConfig;
    }
}
