<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Hydrator\Models;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Model\User;

class HydratorMutation implements MutationInterface
{
    public function createUser(Models $models)
    {
        $model = $models->get('input');

        return true;
    }

    public function updateUser(Models $models)
    {
        $model = $models->get('input');

        return true;
    }
}
