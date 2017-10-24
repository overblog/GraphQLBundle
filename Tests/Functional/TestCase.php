<?php

namespace Overblog\GraphQLBundle\Tests\Functional;

use Overblog\GraphQLBundle\Tests\Functional\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * TestCase.
 */
abstract class TestCase extends WebTestCase
{
    const USER_RYAN = 'ryan';
    const USER_ADMIN = 'admin';
    const ANONYMOUS_USER = null;
    const DEFAULT_PASSWORD = '123';

    /**
     * {@inheritdoc}
     */
    protected static function getKernelClass()
    {
        return TestKernel::class;
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = [])
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        $options['test_case'] = isset($options['test_case']) ? $options['test_case'] : null;

        $env = isset($options['environment']) ? $options['environment'] : 'test'.strtolower($options['test_case']);
        $debug = isset($options['debug']) ? $options['debug'] : true;

        return new static::$class($env, $debug, $options['test_case']);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/OverblogGraphQLBundle/');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        static::$kernel = null;
    }

    protected static function executeGraphQLRequest($query, $rootValue = [], $throwException = false)
    {
        $request = new Request();
        $request->query->set('query', $query);

        $req = static::getContainer()->get('overblog_graphql.request_parser')->parse($request);
        $executor = static::getContainer()->get('overblog_graphql.request_executor');
        $executor->setThrowException($throwException);
        $res = $executor->execute($req, $rootValue);

        return $res->toArray();
    }

    protected static function assertGraphQL($query, array $expectedData = null, array $expectedErrors = null, $rootValue = [])
    {
        $result = static::executeGraphQLRequest($query, $rootValue, true);

        $expected = [];

        if (null !== $expectedData) {
            $expected['data'] = $expectedData;
        }

        if (null !== $expectedErrors) {
            $expected['errors'] = $expectedErrors;
        }

        static::assertEquals($expected, $result, json_encode($result));
    }

    protected static function getContainer()
    {
        return static::$kernel->getContainer();
    }

    protected static function query($query, $username, $testCase, $password = self::DEFAULT_PASSWORD)
    {
        $client = static::createClientAuthenticated($username, $testCase, $password);
        $client->request('GET', '/', ['query' => $query]);

        return $client;
    }

    protected static function createClientAuthenticated($username, $testCase, $password = self::DEFAULT_PASSWORD)
    {
        $client = static::createClient(['test_case' => $testCase]);

        if ($username) {
            $client->setServerParameters([
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => $password,
            ]);
        }

        return $client;
    }

    protected static function assertResponse($query, array $expected, $username, $testCase, $password = self::DEFAULT_PASSWORD)
    {
        $client = self::createClientAuthenticated($username, $testCase, $password);
        $client->request('GET', '/', ['query' => $query]);

        $result = $client->getResponse()->getContent();

        static::assertEquals($expected, json_decode($result, true), $result);

        return $client;
    }
}
