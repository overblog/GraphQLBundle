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

use Overblog\GraphQLBundle\Error\UserWarning;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;

class AccessResolver
{
    public function resolve(callable $accessChecker, callable $resolveCallback, array $resolveArgs = [], $isMutation = false)
    {
        // operation is mutation and is mutation field
        if ($isMutation) {
            $result = $this->checkAccess($accessChecker, null, $resolveArgs, true) ? call_user_func_array($resolveCallback, $resolveArgs) : null;
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
                        return $this->checkAccess($accessChecker, $object, $resolveArgs) ? $object : null;
                    },
                    $result
                );
                break;
            case $result instanceof Connection:
                $result->edges = array_map(
                    function (Edge $edge) use ($accessChecker, $resolveArgs) {
                        $edge->node = $this->checkAccess($accessChecker, $edge->node, $resolveArgs) ? $edge->node : null;

                        return $edge;
                    },
                    $result->edges
                );
                break;
            default:
                $this->checkAccess($accessChecker, $result, $resolveArgs, true);
                break;
        }

        return $result;
    }

    private function checkAccess(callable $accessChecker, $object, array $resolveArgs = [], $throwException = false)
    {
        $resolveArgs[] = $object;

        try {
            $access = (bool) call_user_func_array($accessChecker, $resolveArgs);
        } catch (\Exception $e) {
            $access = false;
        }
        if ($throwException && !$access) {
            throw new UserWarning('Access denied to this field.');
        }

        return $access;
    }
}
