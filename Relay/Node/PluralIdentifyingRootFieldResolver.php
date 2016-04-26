<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Node;

class PluralIdentifyingRootFieldResolver
{
    public function resolve(array $inputs, callable $resolveSingleInput)
    {
        $data = [];

        foreach ($inputs as $input) {
            $data[$input] = call_user_func_array($resolveSingleInput, [$input]);
        }

        return $data;
    }
}
