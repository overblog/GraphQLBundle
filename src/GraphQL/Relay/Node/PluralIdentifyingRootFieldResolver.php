<?php

namespace Overblog\GraphQLBundle\GraphQL\Relay\Node;

use GraphQL\Executor\Promise\PromiseAdapter;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

final class PluralIdentifyingRootFieldResolver implements ResolverInterface, AliasedInterface
{
    /** @var PromiseAdapter */
    private $promiseAdapter;

    public function __construct(PromiseAdapter $promiseAdapter)
    {
        $this->promiseAdapter = $promiseAdapter;
    }

    public function __invoke(array $inputs, $context, $info, callable $resolveSingleInput)
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
    public static function getAliases()
    {
        return ['__invoke' => 'relay_plural_identifying_field'];
    }
}
