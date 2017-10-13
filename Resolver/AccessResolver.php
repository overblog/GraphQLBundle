<?php

namespace Overblog\GraphQLBundle\Resolver;

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
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

    public function resolve(callable $accessChecker, callable $resolveCallback, array $resolveArgs = [], $isMutation = false)
    {
        // operation is mutation and is mutation field
        if ($isMutation) {
            if (!$this->hasAccess($accessChecker, null, $resolveArgs)) {
                throw new UserError('Access denied to this field.');
            }

            $result = call_user_func_array($resolveCallback, $resolveArgs);
        } else {
            $result = $this->filterResultUsingAccess($accessChecker, $resolveCallback, $resolveArgs);
        }

        return $result;
    }

    private function filterResultUsingAccess(callable $accessChecker, callable $resolveCallback, array $resolveArgs = [])
    {
        $result = call_user_func_array($resolveCallback, $resolveArgs);
        if ($result instanceof Promise) {
            $result = $result->adoptedPromise;
        }

        if ($this->promiseAdapter->isThenable($result) || $result instanceof SyncPromise) {
            return $this->promiseAdapter->then(
                new Promise($result, $this->promiseAdapter),
                function ($result) use ($accessChecker, $resolveArgs) {
                    return $this->processFilter($result, $accessChecker, $resolveArgs);
                }
            );
        }

        return $this->processFilter($result, $accessChecker, $resolveArgs);
    }

    private function processFilter($result, $accessChecker, $resolveArgs)
    {
        if (is_array($result)) {
            $result = array_map(
                function ($object) use ($accessChecker, $resolveArgs) {
                    return $this->hasAccess($accessChecker, $object, $resolveArgs) ? $object : null;
                },
                $result
            );
        } elseif ($result instanceof Connection) {
            $result->edges = array_map(
                function (Edge $edge) use ($accessChecker, $resolveArgs) {
                    $edge->node = $this->hasAccess($accessChecker, $edge->node, $resolveArgs) ? $edge->node : null;

                    return $edge;
                },
                $result->edges
            );
        } elseif (!$this->hasAccess($accessChecker, $result, $resolveArgs)) {
            throw new UserWarning('Access denied to this field.');
        }

        return $result;
    }

    private function hasAccess(callable $accessChecker, $object, array $resolveArgs = [])
    {
        $resolveArgs[] = $object;
        $access = (bool) call_user_func_array($accessChecker, $resolveArgs);

        return $access;
    }
}
