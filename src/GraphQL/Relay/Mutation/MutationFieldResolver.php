<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\GraphQL\Relay\Mutation;

use GraphQL\Executor\Promise\PromiseAdapter;
use Overblog\GraphQLBundle\Definition\ArgumentFactory;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Resolver\Resolver;

final class MutationFieldResolver implements ResolverInterface, AliasedInterface
{
    private $promiseAdapter;

    private $argumentFactory;

    public function __construct(PromiseAdapter $promiseAdapter, ArgumentFactory $argumentFactory)
    {
        $this->promiseAdapter = $promiseAdapter;
        $this->argumentFactory = $argumentFactory;
    }

    public function __invoke($args, $context, $info, \Closure $mutateAndGetPayloadCallback)
    {
        $input = $this->argumentFactory->create($args['input']);

        return $this->promiseAdapter->createFulfilled($mutateAndGetPayloadCallback($input, $context, $info))
            ->then(function ($payload) use ($input) {
                Resolver::setObjectOrArrayValue($payload, 'clientMutationId', $input['clientMutationId']);

                return $payload;
            });
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return ['__invoke' => 'relay_mutation_field'];
    }
}
