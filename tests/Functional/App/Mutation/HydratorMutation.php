<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Hydrator\Models;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Model\User;

class HydratorMutation implements MutationInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function createUser(Models $models)
    {
        $model = $models->get('input');

        $this->em->persist($model);
        $this->em->flush();

        return true;
    }

    public function updateUser(Models $models)
    {
        $model = $models->get('input');

        return true;
    }
}
