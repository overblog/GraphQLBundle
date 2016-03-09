<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Overblog\GraphQLBundle\Relay\Node\GlobalId;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ConfigExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction('service', function () {}, function (array $variables, $value) {
                return $variables['container']->get($value);
            }),

            new ExpressionFunction('parameter', function () {}, function (array $variables, $value) {
                return $variables['container']->getParameter($value);
            }),

            new ExpressionFunction('isTypeOf', function () {}, function (array $variables, $className) {
                return $variables['value'] instanceof $className;
            }),

            new ExpressionFunction('resolver', function () {}, function (array $variables, $alias, array $args = []) {
                return $variables['container']->get('overblog_graphql.resolver_resolver')->resolve([$alias, $args]);
            }),

            new ExpressionFunction('mutation', function () {}, function (array $variables, $alias, array $args = []) {
                return $variables['container']->get('overblog_graphql.mutation_resolver')->resolve([$alias, $args]);
            }),

            new ExpressionFunction('globalId', function () {}, function (array $variables, $id, $typeName = null) {
                $type = !empty($typeName) ? $typeName : $variables['info']->parentType->name;

                return GlobalId::toGlobalId($type, $id);
            }),

            new ExpressionFunction('fromGlobalId', function () {}, function (array $variables, $globalId) {
                return GlobalId::fromGlobalId($globalId);
            }),

            new ExpressionFunction('newObject', function () {}, function (array $variables, $className, array $args = []) {
                return (new \ReflectionClass($className))->newInstanceArgs($args);
            }),
        ];
    }
}
