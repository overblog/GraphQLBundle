<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\Address;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\Birthdate;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\Post;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\User;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class HydratorTest extends TestCase
{
    /** @throws DBALException */
    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'hydrator']);

        // Create database
        $conn = DriverManager::getConnection(['driver' => 'pdo_mysql', 'user' => 'root', 'password' => 'root']);
        $manager = $conn->getSchemaManager();
        $manager->dropAndCreateDatabase('overblog_testdb');

        // Update database schema
        $em = self::$container->get(EntityManager::class);
        $classes = array(
            $em->getClassMetadata(User::class),
            $em->getClassMetadata(Post::class),
            $em->getClassMetadata(Birthdate::class),
            $em->getClassMetadata(Address::class)
        );

        $tool = new SchemaTool($em);
        $tool->updateSchema($classes);
    }

    protected function tearDown(): void
    {
        // Clean test database
        $tool = new SchemaTool(self::$container->get(EntityManager::class));
        $tool->dropDatabase();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function simpleUserAndPostsCreate(): void
    {
        $query = <<<'QUERY'
        mutation {
            createUser(
                input: {
                    username: "murtukov"
                    firstName: "Timur"
                    lastName: "Murtukov"
                    posts: [
                        {title: "Lorem Ipsum 1", text: "Lorem ipsum dolor sit amet 1"},
                        {title: "Lorem Ipsum 2", text: "Lorem ipsum dolor sit amet 2"},
                    ]
                }
            )
        }
        QUERY;

        $result = self::executeGraphQLRequest($query);

//        $this->assertTrue(empty($result['errors']));
//        $this->assertTrue($result['data']['noValidation']);
    }

    /**
     * @test
     */
    public function updateEntity()
    {
        $query = <<<'QUERY'
        mutation {
            updateUser(
                input: {
                    id: 15
                    username: "murtukov"
                    firstName: "Timur"
                    lastName: "Murtukov"
                    address: {
                        street: "Proletarskaya 28"
                        city: "Izberbash"
                        zipCode: 368500
                    }
                    friends: [
                        {
                            username: "Clay007"
                            firstName: "Clay"
                            lastName: "Jensen",
                            friends: []
                        },
                        {
                            username: "frodo37"
                            firstName: "Frodo"
                            lastName: "Baggins"
                            friends: []
                        }
                    ]
                    posts: [
                        
                    ]
            })
        }
        QUERY;

        $result = self::executeGraphQLRequest($query);

//        $this->assertTrue(empty($result['errors']));
//        $this->assertTrue($result['data']['noValidation']);
    }
}
