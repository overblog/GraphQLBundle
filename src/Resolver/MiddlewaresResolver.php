<?php
declare(strict_types=1);

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Executor\Promise\PromiseAdapter;

class MiddlewaresResolver
{
    protected $index = 0;
    protected PromiseAdapter $adapter;
    protected $resolver;
    protected array $middlewares = [];

    public function __construct(PromiseAdapter $adapter, callable $resolver, array $middlewares = [])
    {
        $this->adapter = $adapter;
        $this->resolver = $resolver;
        $this->middlewares = $middlewares;
    }

    public function execute(...$args)
    {
        if (isset($this->middlewares[$this->index])) {
            $middleware = $this->middlewares[$this->index];
            $this->index++;
            $next = function (callable $callbackAfter = null) use ($args) {
                $res = $this->execute(...$args);
                if ($callbackAfter) {
                    if ($this->isThenable($res)) {
                        return $this->createPromise(
                            $res,
                            fn ($result) => $callbackAfter($result)
                        );
                    } else {
                        return $callbackAfter($res);
                    }
                } else {
                    return $res;
                }
            };
        } else {
            $middleware = $this->resolver;
            $next = null;
        }

        return call_user_func_array($middleware, [...$args, $next]);
    }

    /**
     * @param mixed $object
     */
    private function isThenable($object): bool
    {
        $object = $this->extractAdoptedPromise($object);

        return $this->adapter->isThenable($object) || $object instanceof SyncPromise;
    }

    /**
     * @param mixed $object
     *
     * @return SyncPromise|mixed|\React\Promise\Promise
     */
    private function extractAdoptedPromise($object)
    {
        if ($object instanceof Promise) {
            $object = $object->adoptedPromise;
        }

        return $object;
    }

    /**
     * @param mixed $promise
     */
    private function createPromise($promise, callable $onFulfilled = null): Promise
    {
        return $this->adapter->then(
            new Promise($this->extractAdoptedPromise($promise), $this->adapter),
            $onFulfilled
        );
    }
}
