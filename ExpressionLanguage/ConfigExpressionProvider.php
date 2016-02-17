<?php

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Overblog\GraphQLBundle\Relay\Node\GlobalId;
use Overblog\GraphQLBundle\Resolver\ResolverInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ConfigExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction('service', function ($arg) {
                return sprintf('$container->get(%s)', $arg);
            }, function (array $variables, $value) {
                return $variables['container']->get($value);
            }),

            new ExpressionFunction('parameter', function ($arg) {
                return sprintf('$container->getParameter(%s)', $arg);
            }, function (array $variables, $value) {
                return $variables['container']->getParameter($value);
            }),

            new ExpressionFunction('isTypeOf', function ($className) {
                return sprintf('$value instanceof %s', $className);
            }, function (array $variables, $className) {
                return $variables['value'] instanceof $className;
            }),

            new ExpressionFunction('resolver', function ($alias, array $args = []) {
                return sprintf('$container->get("overblog_graphql.resolver_resolver")->resolve([%s, $args])', $alias);
            }, function (array $variables, $alias, array $args = []) {
                return $variables['container']->get('overblog_graphql.resolver_resolver')->resolve([$alias, $args]);
            }),

            new ExpressionFunction('mutation', function ($alias, array $args = []) {
                return sprintf('$container->get("overblog_graphql.mutation_resolver")->resolve([%s, $args])', $alias);
            }, function (array $variables, $alias, array $args = []) {
                return $variables['container']->get('overblog_graphql.mutation_resolver')->resolve([$alias, $args]);
            }),

            new ExpressionFunction('globalId', function ($id, $typeName = null)   {
                return sprintf(
                    '\\Overblog\\GraphQLBundle\\Relay\\Node\\GlobalId::toGlobalId(!empty(%s) ? %s : $info->parentType->name, %s)',
                    $typeName,
                    $typeName,
                    $id
                );
            }, function (array $variables, $id, $typeName = null) {
                $type = !empty($typeName)? $typeName : $variables['info']->parentType->name;

                return GlobalId::toGlobalId($type, $id);
            }),

            new ExpressionFunction('fromGlobalId', function ($globalId) {
                return sprintf('\\Overblog\\GraphQLBundle\\Relay\\Node\\GlobalId::fromGlobalId(%s)', $globalId);
            }, function (array $variables, $globalId) {
                return GlobalId::fromGlobalId($globalId);
            }),
        ];
    }
}
