<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser\fixtures\annotations\Deprecated;

use Overblog\GraphQLBundle\Annotation as GQL;

/**
 * @GQL\Type
 * @GQL\FieldsBuilder(builder="MyFieldsBuilder", builderConfig={"param1": "val1"})
 */
class DeprecatedBuilderAttributes
{
    /**
     * @GQL\Field(type="String!")
     */
    protected string $color;
}
