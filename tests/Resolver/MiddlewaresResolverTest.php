<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Exception;
use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use Overblog\GraphQLBundle\Resolver\MiddlewaresResolver;
use PHPUnit\Framework\TestCase;

class MiddlewaresResolverTest extends TestCase
{
    private ReactPromiseAdapter $adapter;

    public function setUp(): void
    {
        $this->adapter = new ReactPromiseAdapter();
    }

    protected function getResolver($resolver, array $middlewares = [])
    {
        return new MiddlewaresResolver($this->adapter, $resolver, $middlewares);
    }

    protected function getPromise($output)
    {
        return $this->adapter->create(function (callable $resolve) use ($output) {
            return $resolve($output);
        });
    }

    public function testSync(): void
    {
        $resolver = fn ($a, $b, $c) => 'value';
        $middlewares = [
            fn ($a, $b, $c, $next) => $next(),
            fn ($a, $b, $c, $next) => $next(),
        ];
        $middlewaresResolver = $this->getResolver($resolver, $middlewares);
        $res = $middlewaresResolver->execute('1', '2', '3');

        $this->assertSame('value', $res);
    }

    public function testPromise(): void
    {
        $resolver = fn ($a, $b, $c) => $this->getPromise('value');
        $middlewares = [
            function ($a, $b, $c, $next) {
                return $this->adapter->create(function (callable $resolve) use ($next) {
                    return $resolve($next(function ($res) {
                        return $res.'_r1';
                    }));
                });
            },
            function ($a, $b, $c, $next) {
                return $this->adapter->create(function (callable $resolve) use ($next) {
                    return $resolve($next(function ($res) {
                        return $res.'_r2';
                    }));
                });
            },
        ];
        $middlewaresResolver = $this->getResolver($resolver, $middlewares);
        $res = $middlewaresResolver->execute('1', '2', '3');
        $res->then(function ($result) {
            $this->assertSame('value_r2_r1', $result);
        });
    }

    public function testReturnEarly(): void
    {
        $resolver = fn ($a, $b, $c) => $this->getPromise('value');

        $middlewares = [
            function ($a, $b, $c, $next) {
                return $next();
            },
            function ($a, $b, $c, $next) {
                return 'foo';
            },
        ];
        $middlewaresResolver = $this->getResolver($resolver, $middlewares);
        $res = $middlewaresResolver->execute('1', '2', '3');
        $this->assertSame('foo', $res);
    }

    public function testException(): void
    {
        $resolver = fn () => 'value';
        $middlewares = [
            function () {
                throw new Exception('Invalid');
            },
        ];

        $middlewaresResolver = $this->getResolver($resolver, $middlewares);
        $this->expectExceptionMessage('Invalid');
        $middlewaresResolver->execute();
    }
}
