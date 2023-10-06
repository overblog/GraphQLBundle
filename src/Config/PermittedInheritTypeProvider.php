<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config;

use Overblog\GraphQLBundle\Enum\TypeEnum;

/**
 * TODO: refactor. This is dirty solution but quick and with minimal impact on existing structure.
 */
class PermittedInheritTypeProvider
{
    /**
     * @return string[]
     */
    public function getAllowedTypes(string $type): array
    {
        return [$type, ...$this->getExtraTypes($type)];
    }

    /**
     * @return string[]
     */
    protected function getExtraTypes(string $type): array
    {
        $allowedTypes = [];
        if (TypeEnum::OBJECT === $type) {
            $allowedTypes[] = TypeEnum::INTERFACE;
        }

        return $allowedTypes;
    }
}
