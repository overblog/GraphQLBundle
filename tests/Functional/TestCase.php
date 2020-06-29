<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Tests\Functional\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * TestCase.
 */
abstract class TestCase extends WebTestCase
{
    public const USER_RYAN = 'ryan';
    public const USER_ADMIN = 'admin';
    public const ANONYMOUS_USER = null;
    public const DEFAULT_PASSWORD = '123';

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

        $options['test_case'] = $options['test_case'] ?? '';

        $env = $options['environment'] ?? 'test'.\strtolower($options['test_case']);
        $debug = $options['debug'] ?? true;

        return new static::$class($env, $debug, $options['test_case']);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(\sys_get_temp_dir().'/OverblogGraphQLBundle/');
    }

    protected function tearDown(): void
    {
        static::ensureKernelShutdown();
    }

    protected static function executeGraphQLRequest(string $query, $rootValue = [], string $schemaName = null)
    {
        $request = new Request();
        $request->query->set('query', $query);

        $req = static::getContainer()->get('overblog_graphql.request_parser')->parse($request);
        $res = static::getContainer()->get('overblog_graphql.request_executor')->execute($schemaName, $req, $rootValue);

        return $res->toArray();
    }

    protected static function assertGraphQL(string $query, array $expectedData = null, array $expectedErrors = null, $rootValue = [], string $schemaName = null): void
    {
        $result = static::executeGraphQLRequest($query, $rootValue, $schemaName);

        $expected = [];

        if (null !== $expectedErrors) {
            $expected['errors'] = $expectedErrors;
        }

        if (null !== $expectedData) {
            $expected['data'] = $expectedData;
        }

        static::assertSame($expected, $result, \json_encode($result));
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
        static::ensureKernelShutdown();
        $client = static::createClient(['test_case' => $testCase]);

        if ($username) {
            $client->setServerParameters([
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => $password,
            ]);
        }

        return $client;
    }

    protected static function assertResponse($query, array $expected, $username, $testCase, $password = self::DEFAULT_PASSWORD, array $variables = null)
    {
        $client = self::createClientAuthenticated($username, $testCase, $password);
        $result = self::sendRequest($client, $query, false, $variables);

        static::assertSame($expected, \json_decode($result, true), $result);

        return $client;
    }

    /**
     * @param Client|KernelBrowser $client
     * @param $query
     * @param bool $isDecoded
     *
     * @return mixed
     */
    protected static function sendRequest($client, $query, $isDecoded = false, array $variables = null)
    {
        $client->request('GET', '/', ['query' => $query, 'variables' => \json_encode($variables)]);
        $result = $client->getResponse()->getContent();

        return $isDecoded ? \json_decode($result, true) : $result;
    }

    public static function expressionFunctionFromPhp($phpFunctionName)
    {
        if (\is_callable([ExpressionFunction::class, 'fromPhp'])) {
            return \call_user_func([ExpressionFunction::class, 'fromPhp'], $phpFunctionName);
        }

        return new ExpressionFunction($phpFunctionName, function () use ($phpFunctionName) {
            return \sprintf('\%s(%s)', $phpFunctionName, \implode(', ', \func_get_args()));
        }, function (): void {});
    }

    /**
     * @param Client|KernelBrowser $client
     */
    protected function disableCatchExceptions($client): void
    {
        if (\is_callable([$client, 'catchExceptions'])) {
            $client->catchExceptions(false);
        }
    }
}
