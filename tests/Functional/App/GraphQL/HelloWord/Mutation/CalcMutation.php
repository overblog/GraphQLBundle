<?php

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
