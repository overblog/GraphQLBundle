<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\GraphQL\HelloWord\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Resolver\ResolverResolver;
use Overblog\GraphQLBundle\Tests\Functional\App\IsolatedResolver\EchoResolver;
use function sprintf;

final class QueryType extends ObjectType implements AliasedInterface
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
                        return $resolver->resolve([sprintf('%s::display', EchoResolver::class), [$args['message']]]);
                    },
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return ['Query'];
    }
}
