<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Transformer;

final class InputType1
{
    /**
     * @var mixed
     */
    public $field1;

    /**
     * @var mixed
     */
    public $field2;

    /**
     * @var mixed
     */
    public $field3;

    /**
     * @var mixed
     */
    public $field4 = 'default_value_when_not_set_in_data';

    /**
     * @var array
     */
    public $field5 = [];

    /**
     * @var mixed
     */
    public $field6;

    public ?string $field7;

    public ?string $field8 = 'default_value_when_not_set_in_data';

    public string $field9 = 'default_value_when_not_set_in_data';
}
