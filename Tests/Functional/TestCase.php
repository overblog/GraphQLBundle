<?php

namespace Overblog\GraphQLBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * TestCase
 */
abstract class TestCase extends WebTestCase
{
    protected static function getKernelClass()
    {
        require_once __DIR__.'/app/AppKernel.php';
        return 'Overblog\GraphQLBundle\Tests\Functional\app\AppKernel';
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = array())
    {
        $class = self::getKernelClass();

        $options['test_case'] = isset($options['test_case']) ? $options['test_case'] : null;

        return new $class(
            isset($options['environment']) ? $options['environment'] : 'overbloggraphbundletest' . strtolower($options['test_case']),
            isset($options['debug']) ? $options['debug'] : true,
            $options['test_case']
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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

        $req = static::$kernel->getContainer()->get('overblog_graphql.request_parser')->parse($request);
        $executor = static::$kernel->getContainer()->get('overblog_graphql.request_executor');
        $executor->setThrowException($throwException);
        $res = $executor->execute($req, $rootValue);

        return $res->toArray();
    }

    protected static function assertGraphQL($query, array $expectedData = null, array $expectedErrors = null, $rootValue = [])
    {
        $result = static::executeGraphQLRequest($query, $rootValue);

        $expected = [];

        if (null !== $expectedData) {
            $expected['data'] = $expectedData;
        }

        if (null !== $expectedErrors) {
            $expected['errors'] = $expectedErrors;
        }

        static::assertEquals($expected, $result, json_encode($result));
    }
}
