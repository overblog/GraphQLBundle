<?php

namespace Overblog\GraphBundle\Tests\Functional;

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
        return 'Overblog\GraphBundle\Tests\Functional\app\AppKernel';
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
        $fs->remove(sys_get_temp_dir().'/OverblogGraphBundle/');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        static::$kernel = null;
    }

    protected static function executeGraphQlRequest($query, $rootValue = [], $enableDebug = false)
    {
        $request = new Request();
        $request->query->set('query', $query);

        $req = static::$kernel->getContainer()->get('overblog_graph.request_parser')->parse($request);
        $executor = static::$kernel->getContainer()->get('overblog_graph.request_executor');
        $executor->setEnabledDebug($enableDebug);
        $res = $executor->execute($req, $rootValue);

        return $res->toArray();
    }

    protected static function assertGraphQl($query, array $expectedData = null, array $expectedErrors = null, $rootValue = [])
    {
        $result = static::executeGraphQlRequest($query, $rootValue);

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
