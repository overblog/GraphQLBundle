<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBaseClassProvider;

use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphQLBundle\Enum\TypeEnum;

final class ObjectTypeBaseClassProvider implements TypeBaseClassProviderInterface
{
    public static function getType(): string
    {
        return TypeEnum::OBJECT;
    }

    public function getBaseClass(): string
    {
        return ObjectType::class;
    }
}
