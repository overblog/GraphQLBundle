<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\GraphQL\Attributes;

use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\Type]
class Type1 extends DemoInterface
{
    #[GQL\Field]
    public string $field1 = 'type1_field1';
}
