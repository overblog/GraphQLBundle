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

use GraphQL\Executor\Promise\Adapter\SyncPromise;
use GraphQL\Executor\Promise\Adapter\SyncPromiseAdapter as BaseSyncPromiseAdapter;
use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface;

class GraphQLPromiseAdapter extends BaseSyncPromiseAdapter implements PromiseAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function isThenable($value)
    {
        $valueOrPromise = $value instanceof Promise ? $value->adoptedPromise : $value;

        return parent::isThenable($valueOrPromise) || $valueOrPromise instanceof SyncPromise;
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
}
