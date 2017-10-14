<?php

namespace Overblog\GraphQLBundle\Executor\Promise\Adapter;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter as BaseReactPromiseAdapter;
use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface;

class ReactPromiseAdapter extends BaseReactPromiseAdapter implements PromiseAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function isThenable($value)
    {
        return parent::isThenable($value instanceof Promise ? $value->adoptedPromise : $value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertThenable($thenable)
    {
        if ($thenable instanceof Promise) {
            return $thenable;
        }

        return parent::convertThenable($thenable);
    }

    /**
     * Synchronously wait when promise completes.
     *
     * @param Promise  $promise
     * @param callable $onProgress
     *
     * @return ExecutionResult
     *
     * @throws \Exception
     */
    public function wait(Promise $promise, callable $onProgress = null)
    {
        if (!$this->isThenable($promise)) {
            throw new \InvalidArgumentException(sprintf('The "%s" method must be call with compatible a Promise.', __METHOD__));
        }
        $wait = true;
        $resolvedValue = null;
        $exception = null;
        /** @var \React\Promise\PromiseInterface $reactPromise */
        $reactPromise = $promise->adoptedPromise;

        $reactPromise->then(function ($values) use (&$resolvedValue, &$wait) {
            $resolvedValue = $values;
            $wait = false;
        }, function ($reason) use (&$exception, &$wait) {
            $exception = $reason;
            $wait = false;
        });

        // wait until promise resolution
        while ($wait) {
            if (null !== $onProgress) {
                $onProgress();
            }
            // less CPU intensive without sacrificing the performance
            usleep(5);
        }

        /** @var \Exception|null $exception */
        if (null !== $exception) {
            throw $exception;
        }

        return $resolvedValue;
    }
}
