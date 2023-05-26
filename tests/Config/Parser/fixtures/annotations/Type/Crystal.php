<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 *
 * @GQL\FieldsBuilder(name="MyFieldsBuilder", config={"param1": "val1"})
 */
#[GQL\Type]
#[GQL\FieldsBuilder(name: 'MyFieldsBuilder', config: ['param1' => 'val1'])]
final class Crystal
{
    /**
     * @GQL\Field(type="String!")
     */
    #[GQL\Field(type: 'String!')]
    public string $color;
}
