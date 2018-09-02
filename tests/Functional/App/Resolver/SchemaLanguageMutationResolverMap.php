<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use Overblog\GraphQLBundle\Resolver\ResolverMap;

class SchemaLanguageMutationResolverMap extends ResolverMap
{
    protected function map()
    {
        return [
            'Mutation' => [
                'resurrectZigZag' => [Characters::class, 'resurrectZigZag'],
            ],
        ];
    }
}
