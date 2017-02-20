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

use GraphQL\Executor\Promise\PromiseAdapter;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Resolver\Resolver;

class MutationFieldResolver
{
    /**
     * @var PromiseAdapter
     */
    private $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function resolve($args, $context, $info, \Closure $mutateAndGetPayloadCallback)
    {
        $input = new Argument($args['input']);

        return $this->promiseAdapter->createFulfilled($mutateAndGetPayloadCallback($input, $context, $info))
            ->then(function ($payload) use ($input) {
                Resolver::setObjectOrArrayValue($payload, 'clientMutationId', $input['clientMutationId']);

                return $payload;
            });
    }
}
