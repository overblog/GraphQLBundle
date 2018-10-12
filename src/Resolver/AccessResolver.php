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
        /** @var ResolveInfo $info */
        $info = $resolveArgs[3];
        // operation is mutation and is mutation field
        $isMutation = 'mutation' === $info->operation->operation && $info->parentType === $info->schema->getMutationType();

        if ($isMutation || $useStrictAccess) {
            if (!$this->hasAccess($accessChecker, null, $resolveArgs)) {
                $exceptionClassName = $isMutation ? UserError::class : UserWarning::class;
                throw new $exceptionClassName('Access denied to this field.');
            }

            $result = \call_user_func_array($resolveCallback, $resolveArgs);
        } else {
            $result = $this->filterResultUsingAccess($accessChecker, $resolveCallback, $resolveArgs);
        }

        return $result;
    }

    private function filterResultUsingAccess(callable $accessChecker, callable $resolveCallback, array $resolveArgs = [])
    {
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

    private function processFilter($result, $accessChecker, $resolveArgs)
    {
        /** @var ResolveInfo $resolveInfo */
        $resolveInfo = $resolveArgs[3];

        if (self::isIterable($result) && $resolveInfo->returnType instanceof ListOfType) {
            foreach ($result as $i => $object) {
                $result[$i] = $this->hasAccess($accessChecker, $object, $resolveArgs) ? $object : null;
            }
        } elseif ($result instanceof Connection) {
            $result->edges = \array_map(
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
        $access = (bool) \call_user_func_array($accessChecker, $resolveArgs);

        return $access;
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
