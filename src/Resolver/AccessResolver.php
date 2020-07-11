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
use function array_map;
use function call_user_func_array;
use function is_iterable;

class AccessResolver
{
    private PromiseAdapter $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    /**
     * @return Promise|mixed|Connection
     */
    public function resolve(callable $accessChecker, callable $resolveCallback, array $resolveArgs = [], bool $useStrictAccess = false)
    {
        if ($useStrictAccess || self::isMutationRootField($resolveArgs[3])) {
            return $this->checkAccessForStrictMode($accessChecker, $resolveCallback, $resolveArgs);
        }

        $resultOrPromise = call_user_func_array($resolveCallback, $resolveArgs);

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

    /**
     * @return Promise|mixed
     */
    private function checkAccessForStrictMode(callable $accessChecker, callable $resolveCallback, array $resolveArgs = [])
    {
        $promiseOrHasAccess = $this->hasAccess($accessChecker, $resolveArgs);
        $callback = function ($hasAccess) use ($resolveArgs, $resolveCallback) {
            if (true === $hasAccess) {
                return call_user_func_array($resolveCallback, $resolveArgs);
            }

            $exceptionClassName = self::isMutationRootField($resolveArgs[3]) ? UserError::class : UserWarning::class;
            throw new $exceptionClassName('Access denied to this field.');
        };

        if ($this->isThenable($promiseOrHasAccess)) {
            return $this->createPromise($promiseOrHasAccess, $callback);
        } else {
            return $callback($promiseOrHasAccess);
        }
    }

    /**
     * @param iterable|object|Connection $result
     *
     * @return Connection|iterable
     */
    private function processFilter($result, callable $accessChecker, array $resolveArgs)
    {
        /** @var ResolveInfo $resolveInfo */
        $resolveInfo = $resolveArgs[3];

        if (is_iterable($result) && $resolveInfo->returnType instanceof ListOfType) {
            foreach ($result as $i => $object) {
                $result[$i] = $this->hasAccess($accessChecker, $resolveArgs, $object) ? $object : null; // @phpstan-ignore-line
            }
        } elseif ($result instanceof Connection) {
            $result->setEdges(array_map(
                function (Edge $edge) use ($accessChecker, $resolveArgs) {
                    $edge->setNode($this->hasAccess($accessChecker, $resolveArgs, $edge->getNode()) ? $edge->getNode() : null);

                    return $edge;
                },
                $result->getEdges()
            ));
        } elseif (!$this->hasAccess($accessChecker, $resolveArgs, $result)) {
            throw new UserWarning('Access denied to this field.');
        }

        return $result; // @phpstan-ignore-line
    }

    /**
     * @param mixed $object
     *
     * @return mixed
     */
    private function hasAccess(callable $accessChecker, array $resolveArgs = [], $object = null)
    {
        $resolveArgs[] = $object;
        $accessOrPromise = call_user_func_array($accessChecker, $resolveArgs);

        return $accessOrPromise;
    }

    /**
     * @param mixed $object
     */
    private function isThenable($object): bool
    {
        $object = $this->extractAdoptedPromise($object);

        return $this->promiseAdapter->isThenable($object) || $object instanceof SyncPromise;
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
        return $this->promiseAdapter->then(
            new Promise($this->extractAdoptedPromise($promise), $this->promiseAdapter),
            $onFulfilled
        );
    }
}
