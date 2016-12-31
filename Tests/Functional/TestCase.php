<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional;

use Overblog\GraphQLBundle\Tests\Functional\app\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * TestCase.
 */
abstract class TestCase extends WebTestCase
{
    /**
     * @var AppKernel[]
     */
    private static $kernels = [];

    protected static function getKernelClass()
    {
        require_once __DIR__.'/app/AppKernel.php';

        return 'Overblog\GraphQLBundle\Tests\Functional\app\AppKernel';
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

        $env = isset($options['environment']) ? $options['environment'] : 'overbloggraphbundletest'.strtolower($options['test_case']);
        $debug = isset($options['debug']) ? $options['debug'] : true;

        $kernelKey = $options['test_case'] ?: '__default__';
        $kernelKey .= '//'.$env.'//'.var_export($debug, true);

        if (!isset(self::$kernels[$kernelKey])) {
            self::$kernels[$kernelKey] = new static::$class($env, $debug, $options['test_case']);
        }

        return self::$kernels[$kernelKey];
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

    protected static function createAndBootKernel(array $options = [])
    {
        static::bootKernel($options);

        static::getContainer()->get('overblog_graphql.cache_compiler')->loadClasses();
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
}
