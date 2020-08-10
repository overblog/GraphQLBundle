<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\Address;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\Birthdate;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\Post;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Entity\User;
use Overblog\GraphQLBundle\Tests\Functional\Hydrator\Fixture\HydratorDatabase;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class HydratorTest extends TestCase
{
    private string $userOwnProps = <<<FRAGMENT
    fragment userOwnProps on User {
        id
        nickname
        firstName
        lastName
    }
    FRAGMENT;

    private string $postOwnProps = <<<FRAGMENT
    fragment postOwnProps on Post {
        id
        title
        text
    }
    FRAGMENT;



    /** @throws DBALException */
    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'hydrator']);

        // Create database
        $conn = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'user' => 'root',
            'password' => 'root'
        ]);

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

        HydratorDatabase::populateDB($em);
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
    public function createUserAndPosts(): void
    {
        $query = <<<QUERY
        mutation {
            createUserAndPosts(
                input: {
                    username: "murtukov"
                    firstName: "Timur"
                    lastName: "Murtukov"
                    posts: [
                        {title: "Lorem Ipsum 1", text: "Lorem ipsum dolor sit amet 1"},
                        {title: "Lorem Ipsum 2", text: "Lorem ipsum dolor sit amet 2"},
                    ]
                }
            ) {
                ...userOwnProps
                posts { ...postOwnProps }
            }
        }
        $this->userOwnProps
        $this->postOwnProps
        QUERY;

        $result = self::executeGraphQLRequest($query);

        /** @var User $user */
        $user = $result['data']['createUserAndPosts'];

        $this->assertNotNull($user['id']);
        $this->assertEquals('murtukov', $user['nickname']);
        $this->assertEquals('Timur', $user['firstName']);
        $this->assertEquals('Murtukov', $user['lastName']);

        $this->assertCount(2, $user['posts']);

        $this->assertNotNull($user['posts'][0]['id']);
        $this->assertNotNull($user['posts'][1]['id']);

        $this->assertEquals('Lorem ipsum dolor sit amet 1', $user['posts'][0]['text']);
        $this->assertEquals('Lorem ipsum dolor sit amet 2', $user['posts'][1]['text']);
        $this->assertEquals('Lorem Ipsum 1', $user['posts'][0]['title']);
        $this->assertEquals('Lorem Ipsum 2', $user['posts'][1]['title']);
    }

    /**
     * @test
     */
    public function updateUserAndPosts()
    {
        $query = <<<QUERY
        mutation {
            updateUserAndPosts(
                input: {
                    id: "4"
                    username: "murtukov"
                    firstName: "Timur"
                    lastName: "Murtukov"
                    posts: [3, 4, 5]
                }
            ) {
                ...userOwnProps
                posts { ...postOwnProps }
            }
        }
        $this->userOwnProps
        $this->postOwnProps
        QUERY;

        $result = self::executeGraphQLRequest($query);
    }
}
