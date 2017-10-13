<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\GraphQL\HelloWord\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Resolver\ResolverResolver;
use Overblog\GraphQLBundle\Tests\Functional\App\IsolatedResolver\EchoResolver;

final class QueryType extends ObjectType
{
    public function __construct(ResolverResolver $resolver)
    {
        parent::__construct([
            'name' => 'Query',
            'fields' => [
                'echo' => [
                    'type' => Type::string(),
                    'args' => [
                        'message' => ['type' => Type::string()],
                    ],
                    'resolve' => function ($root, $args) use ($resolver) {
                        return $resolver->resolve([
                            EchoResolver::class,
                            [$args['message']],
                        ]);
                    },
                ],
            ],
        ]);
    }
}
