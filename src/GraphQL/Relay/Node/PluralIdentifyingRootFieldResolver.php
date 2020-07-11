<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\GraphQL\Relay\Node;

use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use function call_user_func_array;

final class PluralIdentifyingRootFieldResolver implements ResolverInterface, AliasedInterface
{
    private PromiseAdapter $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    /**
     * @param mixed $context
     */
    public function __invoke(array $inputs, $context, ResolveInfo $info, callable $resolveSingleInput): Promise
    {
        $data = [];

        foreach ($inputs as $input) {
            $data[$input] = $this->promiseAdapter->createFulfilled(call_user_func_array($resolveSingleInput, [$input, $context, $info]));
        }

        return $this->promiseAdapter->all($data);
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return ['__invoke' => 'relay_plural_identifying_field'];
    }
}
