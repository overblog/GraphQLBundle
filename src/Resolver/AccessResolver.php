<?php

declare(strict_types=1);

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

        $result = \call_user_func_array($resolveCallback, $resolveArgs);

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

    private static function isMutationRootField(ResolveInfo $info): bool
    {
        return 'mutation' === $info->operation->operation && $info->parentType === $info->schema->getMutationType();
    }

    private function checkAccessForStrictMode(callable $accessChecker, callable $resolveCallback, array $resolveArgs = [])
    {
        if (!$this->hasAccess($accessChecker, $resolveArgs)) {
            $exceptionClassName = self::isMutationRootField($resolveArgs[3]) ? UserError::class : UserWarning::class;
            throw new $exceptionClassName('Access denied to this field.');
        }

        return \call_user_func_array($resolveCallback, $resolveArgs);
    }

    private function processFilter($result, $accessChecker, $resolveArgs)
    {
        /** @var ResolveInfo $resolveInfo */
        $resolveInfo = $resolveArgs[3];

        if (\is_iterable($result) && $resolveInfo->returnType instanceof ListOfType) {
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

    private function hasAccess(callable $accessChecker, array $resolveArgs = [], $object = null): bool
    {
        $resolveArgs[] = $object;
        $access = (bool) \call_user_func_array($accessChecker, $resolveArgs);

        return $access;
    }
}
