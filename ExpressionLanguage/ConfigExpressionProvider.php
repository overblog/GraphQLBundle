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
use Overblog\GraphQLBundle\Relay\Node\GlobalId;
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
                }
            ),

            new ExpressionFunction(
                'parameter',
                function ($value) {
                    return sprintf('$container->getParameter(%s)', $value);
                }
            ),

            new ExpressionFunction(
                'isTypeOf',
                function ($className) {
                    return sprintf('($className = %s) && $value instanceof $className', $className);
                }
            ),

            new ExpressionFunction(
                'resolver',
                function ($alias, $args = '[]') {
                    return sprintf('$container->get(\'overblog_graphql.resolver_resolver\')->resolve([%s, %s])', $alias, $args);
                }
            ),

            new ExpressionFunction(
                'mutateAndGetPayloadCallback',
                function ($mutateAndGetPayload) {
                    $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                    $code .= 'return '.$mutateAndGetPayload.'; }';

                    return $code;
                }
            ),

            new ExpressionFunction(
                'idFetcherCallback',
                function ($idFetcher) {
                    $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                    $code .= 'return '.$idFetcher.'; }';

                    return $code;
                }
            ),

            new ExpressionFunction(
                'resolveSingleInputCallback',
                function ($resolveSingleInput) {
                    $code = 'function ($value) use ('.TypeGenerator::USE_FOR_CLOSURES.', $args, $context, $info) { ';
                    $code .= 'return '.$resolveSingleInput.'; }';

                    return $code;
                }
            ),

            new ExpressionFunction(
                'mutation',
                function ($alias, $args = '[]') {
                    return sprintf('$container->get(\'overblog_graphql.mutation_resolver\')->resolve([%s, %s])', $alias, $args);
                }
            ),

            new ExpressionFunction(
                'globalId',
                function ($id, $typeName = null) {
                    $typeNameEmpty = null === $typeName || '""' === $typeName || 'null' === $typeName || 'false' === $typeName;

                    return sprintf(
                        '%s::toGlobalId(%s, %s)',
                        GlobalId::class,
                        sprintf($typeNameEmpty ? '$info->parentType->name' : '%s', $typeName),
                        $id
                    );
                }
            ),

            new ExpressionFunction(
                'fromGlobalId',
                function ($globalId) {
                    return sprintf(
                        '%s::fromGlobalId(%s)',
                        GlobalId::class,
                        $globalId
                    );
                }
            ),

            new ExpressionFunction(
                'newObject',
                function ($className, $args = '[]') {
                    return sprintf('(new \ReflectionClass(%s))->newInstanceArgs(%s)', $className, $args);
                }
            ),
        ];
    }
}
