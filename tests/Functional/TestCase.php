<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction;
use Overblog\GraphQLBundle\Tests\Functional\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use function call_user_func;
use function func_get_args;
use function implode;
use function is_callable;
use function json_decode;
use function json_encode;
use function sprintf;
use function strtolower;
use function sys_get_temp_dir;

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

        $env = $options['environment'] ?? 'test'.strtolower($options['test_case']);
        $debug = $options['debug'] ?? true;

        return new static::$class($env, $debug, $options['test_case']);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/OverblogGraphQLBundle/');
    }

    protected function tearDown(): void
    {
        static::ensureKernelShutdown();
    }

    protected static function executeGraphQLRequest(string $query, array $rootValue = [], string $schemaName = null): array
    {
        $request = new Request();
        $request->query->set('query', $query);

        // @phpstan-ignore-next-line
        $req = static::getContainer()->get('overblog_graphql.request_parser')->parse($request);
        // @phpstan-ignore-next-line
        $res = static::getContainer()->get('overblog_graphql.request_executor')->execute($schemaName, $req, $rootValue);

        return $res->toArray();
    }

    protected static function assertGraphQL(string $query, array $expectedData = null, array $expectedErrors = null, array $rootValue = [], string $schemaName = null): void
    {
        $result = static::executeGraphQLRequest($query, $rootValue, $schemaName);

        $expected = [];

        if (null !== $expectedErrors) {
            $expected['errors'] = $expectedErrors;
        }

        if (null !== $expectedData) {
            $expected['data'] = $expectedData;
        }

        static::assertSame($expected, $result, json_encode($result));
    }

    protected static function getContainer(): ContainerInterface
    {
        return static::$kernel->getContainer();
    }

    protected static function query(string $query, string $username, string $testCase, string $password = self::DEFAULT_PASSWORD): KernelBrowser
    {
        $client = static::createClientAuthenticated($username, $testCase, $password);
        $client->request('GET', '/', ['query' => $query]);

        return $client;
    }

    protected static function createClientAuthenticated(?string $username, string $testCase, ?string $password = self::DEFAULT_PASSWORD): KernelBrowser
    {
        static::ensureKernelShutdown();
        $client = static::createClient(['test_case' => $testCase]);

        if (null !== $username) {
            $client->setServerParameters([
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => $password,
            ]);
        }

        return $client;
    }

    protected static function assertResponse(string $query, array $expected, ?string $username, string $testCase, ?string $password = self::DEFAULT_PASSWORD, array $variables = null): KernelBrowser
    {
        $client = self::createClientAuthenticated($username, $testCase, $password);
        $result = self::sendRequest($client, $query, false, $variables);

        static::assertSame($expected, json_decode($result, true), $result);

        return $client;
    }

    /**
     * @return mixed
     */
    protected static function sendRequest(KernelBrowser $client, string $query, bool $isDecoded = false, array $variables = null)
    {
        $client->request('GET', '/', ['query' => $query, 'variables' => json_encode($variables)]);
        $result = $client->getResponse()->getContent();

        return $isDecoded ? json_decode($result, true) : $result;
    }

    /**
     * @return mixed|ExpressionFunction
     */
    public static function expressionFunctionFromPhp(string $phpFunctionName)
    {
        if (is_callable([ExpressionFunction::class, 'fromPhp'])) {
            return call_user_func([ExpressionFunction::class, 'fromPhp'], $phpFunctionName);
        }

        return new ExpressionFunction($phpFunctionName, fn () => (
            sprintf('\%s(%s)', $phpFunctionName, implode(', ', func_get_args()))
        ), function (): void {});
    }

    /**
     * @param KernelBrowser $client
     */
    protected function disableCatchExceptions($client): void
    {
        if (is_callable([$client, 'catchExceptions'])) {
            $client->catchExceptions(false);
        }
    }
}
