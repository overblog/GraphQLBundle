<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\App\GraphQL\HelloWord\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Resolver\MutationResolver;

final class MutationType extends ObjectType
{
    public function __construct(MutationResolver $mutator)
    {
        parent::__construct([
            'name' => 'Calc',
            'fields' => [
                'sum' => [
                    'type' => Type::int(),
                    'args' => [
                        'x' => ['type' => Type::int()],
                        'y' => ['type' => Type::int()],
                    ],
                    'resolve' => function ($root, $args) use ($mutator) {
                        return $mutator->resolve([
                            'sum',
                            [$args['x'], $args['y']],
                        ]);
                    },
                ],
            ],
        ]);
    }
}
