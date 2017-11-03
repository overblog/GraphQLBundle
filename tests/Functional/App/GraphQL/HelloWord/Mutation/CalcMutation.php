<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\App\GraphQL\HelloWord\Mutation;

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;

final class CalcMutation implements MutationInterface, AliasedInterface
{
    public function add($x, $y)
    {
        return $x + $y;
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases()
    {
        return ['add' => 'sum'];
    }
}
