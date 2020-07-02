<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Model\User;

class HydratorMutation implements MutationInterface
{
    public function createUser($model)
    {
        $x = $model;


        return (bool) $x;
    }

    public function updateUser(User $user)
    {

    }
}
