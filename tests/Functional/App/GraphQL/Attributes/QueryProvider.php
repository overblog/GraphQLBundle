<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\GraphQL\Attributes;

use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Provider]
final class QueryProvider
{
    #[GQL\Query(type: '[DemoInterface]')]
    public function getDemoItems(): array
    {
        return [
            new Type1(),
            new Type2(),
        ];
    }
}
