<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBaseClassProvider;

use GraphQL\Type\Definition\Type;

interface TypeBaseClassProviderInterface
{
    public static function getType(): string;

    /**
     * @return class-string<Type>
     */
    public function getBaseClass(): string;
}
