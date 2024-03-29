<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Transformer;

final class InputType4
{
    /**
     * @var mixed
     */
    public $field1 = "default_value_field1";

    /**
     * @var mixed
     */
    public $field2 = ["v1", "v2"];

    /**
     * @var mixed
     */
    public $field3 = 5;

    /**
     * @var mixed
     */
    public $field4;
}
