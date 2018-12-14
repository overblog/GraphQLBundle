<?php

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;

class AccessResolver
{
    /** @var PromiseAdapter */
    private $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function resolve(callable $accessChecker, callable $resolveCallback, array $resolveArgs = [], $useStrictAccess = false)
    {
        if ($useStrictAccess || self::isMutationRootField($resolveArgs[3])) {
            return $this->checkAccessForStrictMode($accessChecker, $resolveCallback, $resolveArgs);
        }

        $resultOrPromise = \call_user_func_array($resolveCallback, $resolveArgs);

        if ($this->isThenable($resultOrPromise)) {
            return $this->createPromise(
                $resultOrPromise,
                function ($result) use ($accessChecker, $resolveArgs) {
                    return $this->processFilter($result, $accessChecker, $resolveArgs);
                }
            );
        }

        return $this->processFilter($resultOrPromise, $accessChecker, $resolveArgs);
    }

    private static function isMutationRootField(ResolveInfo $info)
    {
        return 'mutation' === $info->operation->operation && $info->parentType === $info->schema->getMutationType();
    }

    private function checkAccessForStrictMode(callable $accessChecker, callable $resolveCallback, array $resolveArgs = [])
    {
        $promiseOrHasAccess = $this->hasAccess($accessChecker, $resolveArgs);
        $callback = function ($hasAccess) use ($resolveArgs, $resolveCallback) {
            if (!$hasAccess) {
                $exceptionClassName = self::isMutationRootField($resolveArgs[3]) ? UserError::class : UserWarning::class;
                throw new $exceptionClassName('Access denied to this field.');
            }

            return \call_user_func_array($resolveCallback, $resolveArgs);
        };

        if ($this->isThenable($promiseOrHasAccess)) {
            return $this->createPromise($promiseOrHasAccess, $callback);
        } else {
            return $callback($promiseOrHasAccess);
        }
    }

    private function processFilter($result, $accessChecker, $resolveArgs)
    {
        /** @var ResolveInfo $resolveInfo */
        $resolveInfo = $resolveArgs[3];

        if (self::isIterable($result) && $resolveInfo->returnType instanceof ListOfType) {
            foreach ($result as $i => $object) {
                $result[$i] = $this->hasAccess($accessChecker, $resolveArgs, $object) ? $object : null;
            }
        } elseif ($result instanceof Connection) {
            $result->edges = \array_map(
                function (Edge $edge) use ($accessChecker, $resolveArgs) {
                    $edge->node = $this->hasAccess($accessChecker, $resolveArgs, $edge->node) ? $edge->node : null;

                    return $edge;
                },
                $result->edges
            );
        } elseif (!$this->hasAccess($accessChecker, $resolveArgs, $result)) {
            throw new UserWarning('Access denied to this field.');
        }

        return $result;
    }

    private function hasAccess(callable $accessChecker, array $resolveArgs = [], $object = null)
    {
        $resolveArgs[] = $object;
        $accessOrPromise = \call_user_func_array($accessChecker, $resolveArgs);

        return $accessOrPromise;
    }

    private function isThenable($object)
    {
        $object = $this->extractAdoptedPromise($object);

        return $this->promiseAdapter->isThenable($object) || $object instanceof SyncPromise;
    }

    private function extractAdoptedPromise($object)
    {
        if ($object instanceof Promise) {
            $object = $object->adoptedPromise;
        }

        return $object;
    }

    private function createPromise($promise, callable $onFulfilled = null)
    {
        return $this->promiseAdapter->then(
            new Promise($this->extractAdoptedPromise($promise), $this->promiseAdapter),
            $onFulfilled
        );
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    private static function isIterable($data)
    {
        if (\function_exists('is_iterable')) {
            return \is_iterable($data);
        } else {
            return \is_array($data) || (\is_object($data) && ($data instanceof \Traversable));
        }
    }
}
