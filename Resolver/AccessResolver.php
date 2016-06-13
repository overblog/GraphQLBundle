<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver;

use Overblog\GraphQLBundle\Error\UserError;
use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;

class AccessResolver
{
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

        switch (true) {
            case is_array($result):
                $result = array_map(
                    function ($object) use ($accessChecker, $resolveArgs) {
                        return $this->hasAccess($accessChecker, $object, $resolveArgs) ? $object : null;
                    },
                    $result
                );
                break;
            case $result instanceof Connection:
                $result->edges = array_map(
                    function (Edge $edge) use ($accessChecker, $resolveArgs) {
                        $edge->node = $this->hasAccess($accessChecker, $edge->node, $resolveArgs) ? $edge->node : null;

                        return $edge;
                    },
                    $result->edges
                );
                break;
            default:
                if (!$this->hasAccess($accessChecker, $result, $resolveArgs)) {
                    throw new UserWarning('Access denied to this field.');
                }
                break;
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
