<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Mutation;

use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Resolver\Resolver;

class MutationFieldResolver
{
    public function resolve($args, \Closure $mutateAndGetPayloadCallback)
    {
        $input = new Argument($args['input']);

        $payload = $mutateAndGetPayloadCallback($input);
        Resolver::setObjectOrArrayValue($payload, 'clientMutationId', $input['clientMutationId']);

        return $payload;
    }
}
