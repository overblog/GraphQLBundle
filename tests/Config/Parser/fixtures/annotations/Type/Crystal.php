<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Type;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type(builders={@GQL\FieldsBuilder(builder="MyFieldsBuilder", builderConfig={"param1": "val1"})})
 */
class Crystal
{
    /**
     * @GQL\Field(type="String!")
     */
    protected string $color;
}
