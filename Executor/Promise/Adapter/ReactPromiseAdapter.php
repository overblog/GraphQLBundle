<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @param Promise $promise
     *
     * @return ExecutionResult
     *
     * @throws \Exception
     */
    public function wait(Promise $promise)
    {
        if (!$this->isThenable($promise)) {
            return;
        }
        $wait = true;
        $resolvedValue = null;
        $exception = null;
        /** @var \React\Promise\PromiseInterface $reactPromise */
        $reactPromise = $promise->adoptedPromise;

        while ($wait) {
            $reactPromise->then(function ($values) use (&$resolvedValue, &$wait) {
                $resolvedValue = $values;
                $wait = false;
            }, function ($reason) use (&$exception, &$wait) {
                $exception = $reason;
                $wait = false;
            });
        }

        if ($exception instanceof \Exception) {
            throw $exception;
        }

        return $resolvedValue;
    }
}
