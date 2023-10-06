<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBaseClassProvider;

use GraphQL\Type\Definition\UnionType;
use Overblog\GraphQLBundle\Enum\TypeEnum;

final class UnionTypeBaseClassProvider implements TypeBaseClassProviderInterface
{
    public static function getType(): string
    {
        return TypeEnum::UNION;
    }

    public function getBaseClass(): string
    {
        return UnionType::class;
    }
}
