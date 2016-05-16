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

use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ConfigExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction(
                'service',
                function ($value) {
                    return sprintf('$container->get(%s)', $value);
                },
                function () {}
            ),

            new ExpressionFunction(
                'parameter',
                function ($value) {
                    return sprintf('$container->getParameter(%s)', $value);
                },
                function () {}
            ),

            new ExpressionFunction(
                'isTypeOf',
                function ($className) {
                    return sprintf('($className = %s) && $value instanceof $className', $className);
                },
                function () {}
            ),

            new ExpressionFunction(
                'resolver',
                function ($alias, $args = '[]') {
                    return sprintf('$container->get(\'overblog_graphql.resolver_resolver\')->resolve([%s, %s])', $alias, $args);
                },
                function () {}
            ),

            new ExpressionFunction(
                'mutateAndGetPayloadCallback',
                function ($mutateAndGetPayload) {
                    $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $info) { ';
                    $code .= 'return '.$mutateAndGetPayload.'; }';

                    return $code;
                },
                function () {}
            ),

            new ExpressionFunction(
                'mutateAndGetPayloadCallback',
                function ($mutateAndGetPayload) {
                    $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $info) { ';
                    $code .= 'return '.$mutateAndGetPayload.'; }';

                    return $code;
                },
                function () {}
            ),

            new ExpressionFunction(
                'idFetcherCallback',
                function ($idFetcher) {
                    $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $info) { ';
                    $code .= 'return '.$idFetcher.'; }';

                    return $code;
                },
                function () {}
            ),

            new ExpressionFunction(
                'resolveSingleInputCallback',
                function ($resolveSingleInput) {
                    $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $info) { ';
                    $code .= 'return '.$resolveSingleInput.'; }';

                    return $code;
                },
                function () {}
            ),

            new ExpressionFunction(
                'mutation',
                function ($alias, $args = '[]') {
                    return sprintf('$container->get(\'overblog_graphql.mutation_resolver\')->resolve([%s, %s])', $alias, $args);
                },
                function () {}
            ),

            new ExpressionFunction(
                'globalId',
                function ($id, $typeName = null) {
                    $typeNameEmpty = null === $typeName || '""' === $typeName || 'null' === $typeName || 'false' === $typeName;

                    return sprintf(
                        '\\Overblog\\GraphQLBundle\\Relay\\Node\\GlobalId::toGlobalId(%s, %s)',
                        sprintf($typeNameEmpty ? '$info->parentType->name' : '%s', $typeName),
                        $id
                    );
                },
                function () {}
            ),

            new ExpressionFunction(
                'fromGlobalId',
                function ($globalId) {
                    return sprintf(
                        '\\Overblog\\GraphQLBundle\\Relay\\Node\\GlobalId::fromGlobalId(%s)',
                        $globalId
                    );
                },
                function () {}
            ),

            new ExpressionFunction(
                'newObject',
                function ($className, $args = '[]') {
                    return sprintf('(new \ReflectionClass(%s))->newInstanceArgs(%s)', $className, $args);
                },
                function () {}
            ),
        ];
    }
}
