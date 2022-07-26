<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBaseClassProvider;

use GraphQL\Type\Definition\InputObjectType;
use Overblog\GraphQLBundle\Enum\TypeEnum;

final class InputObjectTypeBaseClassProvider implements TypeBaseClassProviderInterface
{
    public static function getType(): string
    {
        return TypeEnum::INPUT_OBJECT;
    }

    public function getBaseClass(): string
    {
        return InputObjectType::class;
    }
}
