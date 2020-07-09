<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\GraphQL\Relay\Mutation;

use Closure;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentFactory;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use function is_array;
use function is_object;

final class MutationFieldResolver implements ResolverInterface, AliasedInterface
{
    private PromiseAdapter $promiseAdapter;
    private ArgumentFactory $argumentFactory;

    public function __construct(PromiseAdapter $promiseAdapter, ArgumentFactory $argumentFactory)
    {
        $this->promiseAdapter = $promiseAdapter;
        $this->argumentFactory = $argumentFactory;
    }

    /**
     * @param mixed $context
     */
    public function __invoke(ArgumentInterface $args, $context, ResolveInfo $info, Closure $mutateAndGetPayloadCallback): Promise
    {
        $input = $this->argumentFactory->create($args['input']);

        return $this->promiseAdapter->createFulfilled($mutateAndGetPayloadCallback($input, $context, $info))
            ->then(function ($payload) use ($input) {
                $this->setObjectOrArrayValue($payload, 'clientMutationId', $input['clientMutationId']);

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

    /**
     * @param object|array $objectOrArray
     * @param mixed        $value
     */
    private function setObjectOrArrayValue(&$objectOrArray, string $fieldName, $value): void
    {
        if (is_array($objectOrArray)) {
            $objectOrArray[$fieldName] = $value;
        } elseif (is_object($objectOrArray)) {
            $objectOrArray->$fieldName = $value;
        }
    }
}
