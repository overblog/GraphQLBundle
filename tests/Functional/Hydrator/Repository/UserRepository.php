<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Repository;

use Doctrine\ORM\EntityRepository;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\{Address, User, Birthdate, Post};

class UserRepository extends EntityRepository
{
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        $user = new User;

        $user->id = 15;
        $user->nickname = "murtukov";
        $user->firstName = "Timur";
        $user->lastName = "Murtukov";

        $user->address = new Address();
        $user->address->id = 21;
        $user->address->street = "Proletarskaya 28";
        $user->address->city = "Izberbash";
        $user->address->zipCode = 368500;

        $user->birth = new Birthdate();
        $user->birth->id = 33;
        $user->birth->day = 17;
        $user->birth->month = 3;
        $user->birth->year = 1990;

        $user->friends = [
            new User(),
            new User(),
            new User(),
        ];

        return $user;
    }
}