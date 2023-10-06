<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBaseClassProvider;

use Overblog\GraphQLBundle\Definition\Type\CustomScalarType;
use Overblog\GraphQLBundle\Enum\TypeEnum;

final class CustomScalarTypeBaseClassProvider implements TypeBaseClassProviderInterface
{
    public static function getType(): string
    {
        return TypeEnum::CUSTOM_SCALAR;
    }

    public function getBaseClass(): string
    {
        return CustomScalarType::class;
    }
}
