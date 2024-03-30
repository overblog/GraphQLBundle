<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\GraphQL\Attributes;

use Overblog\GraphQLBundle\Annotation as GQL;

#[GQL\TypeInterface]
abstract class DemoInterface
{
    #[GQL\Field]
    public string $fieldInterface = 'field_interface';
}
