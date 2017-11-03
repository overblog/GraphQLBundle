<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\GraphQL\Relay;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;

final class GlobalID extends ExpressionFunction
{
    public function __construct($name = 'globalId')
    {
        parent::__construct(
            $name,
            function ($id, $typeName = null) {
                $typeNameEmpty = null === $typeName || '""' === $typeName || 'null' === $typeName || 'false' === $typeName;

                return sprintf(
                    '\%s::toGlobalId(%s, %s)',
                    \Overblog\GraphQLBundle\Relay\Node\GlobalId::class,
                    sprintf($typeNameEmpty ? '$info->parentType->name' : '%s', $typeName),
                    $id
                );
            }
        );
    }
}
