<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\{Address, Post, User, Birthdate};

class HydratorDatabase
{
    public static function populateDB(EntityManagerInterface $manager)
    {
        // Create users with another entities
        foreach (self::userProvider() as [$nickname, $firstName, $lastName, $birth, $address, $posts]) {
            $user = new User();
            $user->nickname = $nickname;
            $user->firstName = $firstName;
            $user->lastName = $lastName;

            if (null !== $birth) {
                $birthdate = new Birthdate();
                $birthdate->populateFromArray($birth);
                $user->birth = $birthdate;
            }

            if (null !== $address) {
                $user->address = (new Address())->populateFromArray($address);
            }

            if (null !== $posts) {
                foreach ($posts as $post) {
                    $user->posts[] = (new Post())->populateFromArray($address);
                }
            }

            $manager->persist($user);
        }

        // Create standalone birthdates
        foreach (self::birthdateProvider() as $values) {
            $birthdate = new Birthdate();
            $birthdate->populateFromArray($values);
            $manager->persist($birthdate);
        }

        // Create standalone addresses
        foreach (self::addressProvider() as $values) {
            $address = new Address();
            $address->populateFromArray($values);
            $manager->persist($address);
        }

        // Create standalone posts
        foreach (self::postProvider() as $values) {
            $post = new Post();
            $post->populateFromArray($values);
            $manager->persist($post);
        }

        $manager->flush();
    }

    /**
     * @return array<array<mixed>>
     */
    private static function userProvider(): array
    {
        return [
            ['user1', 'Michael1', 'Jackson1', null, null, null],
            ['user2', 'Michael2', 'Jackson2', null, null, null],
            ['user3', 'Michael3', 'Jackson3', [29, 8, 1958], null, null],
            ['user4', 'Michael4', 'Jackson4', [17, 3, 1925], ['Wall Street', 'New York', 10001], null],
            [
                'user5',
                'Michael5',
                'Jackson5',
                [25, 12, 2000],
                ['Jackson Street', 'Las Vegas', 88901],
                [
                    ['Lorem Ipsum 1', 'Lorem ipsum dolor sit amet 1'],
                    ['Lorem Ipsum 2', 'Lorem ipsum dolor sit amet 2']
                ]
            ],
        ];
    }

    /**
     * @return string[][]
     */
    private static function postProvider(): array
    {
        return [
            ['Lorem Ipsum 3', 'Lorem ipsum dolor sit amet 3'],
            ['Lorem Ipsum 4', 'Lorem ipsum dolor sit amet 4'],
            ['Lorem Ipsum 5', 'Lorem ipsum dolor sit amet 5'],
            ['Lorem Ipsum 6', 'Lorem ipsum dolor sit amet 6'],
        ];
    }

    /**
     * @return int[][]
     */
    private static function birthdateProvider(): array
    {
        return [
            [1, 1, 2001],
            [2, 2, 2002],
            [3, 3, 2003],
            [4, 4, 2004],
            [5, 5, 2005],
        ];
    }

    /**
     * @return array<>
     */
    private static function addressProvider(): array
    {
        return [
            ['11th Street', 'Jacksonville', 32221],
            ['12th Street', 'Jacksonville', 32220],
            ['13th Street', 'Jacksonville', 32206],
            ['14th Street', 'Jacksonville', 32206],
            ['15th Street', 'Jacksonville', 32206],
        ];
    }
}