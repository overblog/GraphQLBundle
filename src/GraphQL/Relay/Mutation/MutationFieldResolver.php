<?php

namespace Overblog\GraphQLBundle\GraphQL\Relay\Mutation;

use GraphQL\Executor\Promise\PromiseAdapter;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Resolver\Resolver;

final class MutationFieldResolver implements ResolverInterface, AliasedInterface
{
    /** @var PromiseAdapter */
    private $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function __invoke($args, $context, $info, \Closure $mutateAndGetPayloadCallback)
    {
        $input = new Argument($args['input']);

        return $this->promiseAdapter->createFulfilled($mutateAndGetPayloadCallback($input, $context, $info))
            ->then(function ($payload) use ($input) {
                Resolver::setObjectOrArrayValue($payload, 'clientMutationId', $input['clientMutationId']);

                return $payload;
            });
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases()
    {
        return ['__invoke' => 'relay_mutation_field'];
    }
}
