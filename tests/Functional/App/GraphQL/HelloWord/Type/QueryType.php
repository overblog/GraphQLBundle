<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\GraphQL\HelloWord\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Resolver\QueryResolver;
use Overblog\GraphQLBundle\Tests\Functional\App\IsolatedResolver\EchoQuery;
use function sprintf;

final class QueryType extends ObjectType implements AliasedInterface
{
    public function __construct(QueryResolver $resolver)
    {
        parent::__construct([
            'name' => 'Query',
            'fields' => [
                'echo' => [
                    'type' => Type::string(),
                    'args' => [
                        'message' => ['type' => Type::string()],
                    ],
                    'resolve' => fn ($root, $args) => $resolver->resolve([sprintf('%s::display', EchoQuery::class), [$args['message']]]),
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
