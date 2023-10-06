<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBaseClassProvider;

use GraphQL\Type\Definition\InterfaceType;
use Overblog\GraphQLBundle\Enum\TypeEnum;

final class InterfaceTypeBaseClassProvider implements TypeBaseClassProviderInterface
{
    public static function getType(): string
    {
        return TypeEnum::INTERFACE;
    }

    public function getBaseClass(): string
    {
        return InterfaceType::class;
    }
}
