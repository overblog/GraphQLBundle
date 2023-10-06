<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBaseClassProvider;

use GraphQL\Type\Definition\EnumType;
use Overblog\GraphQLBundle\Enum\TypeEnum;

final class EnumTypeBaseClassProvider implements TypeBaseClassProviderInterface
{
    public static function getType(): string
    {
        return TypeEnum::ENUM;
    }

    public function getBaseClass(): string
    {
        return EnumType::class;
    }
}
