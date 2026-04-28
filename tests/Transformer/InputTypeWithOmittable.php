<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Transformer;

use Overblog\GraphQLBundle\Definition\Omittable;

final class InputTypeWithOmittable
{
    /**
     * @var Omittable<string|null>
     */
    public Omittable $nullableString;

    /**
     * @var Omittable<InputType1|null>
     */
    public Omittable $nestedInput;

    /**
     * @var Omittable<array<string>|null>
     */
    public Omittable $stringList;

    public ?string $regularNullable = 'default_value';
}
