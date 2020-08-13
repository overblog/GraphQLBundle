<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\GraphQL\HelloWord\Mutation;

use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;

final class CalcMutation implements MutationInterface, AliasedInterface
{
    public function add(int $x, int $y): int
    {
        return $x + $y;
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return ['add' => 'sum'];
    }
}
