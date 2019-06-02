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

    private static function isMutationRootField(ResolveInfo $info): bool
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
            $promiseOrHasAccess = $this->extractAdoptedPromise($promiseOrHasAccess);
            if ($promiseOrHasAccess instanceof SyncPromise) {
                $promiseOrHasAccess = $promiseOrHasAccess->result;
            }

            return $callback($promiseOrHasAccess);
        }
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
            $result->setEdges(\array_map(
                function (Edge $edge) use ($accessChecker, $resolveArgs) {
                    $edge->setNode($this->hasAccess($accessChecker, $resolveArgs, $edge->getNode()) ? $edge->getNode() : null);

                    return $edge;
                },
                $result->getEdges()
            ));
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

    private function isThenable($object): bool
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

    private function createPromise($promise, callable $onFulfilled = null): Promise
    {
        return $this->promiseAdapter->then(
            new Promise($this->extractAdoptedPromise($promise), $this->promiseAdapter),
            $onFulfilled
        );
    }
}
