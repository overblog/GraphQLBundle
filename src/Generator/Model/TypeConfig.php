<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Model;

use Overblog\GraphQLBundle\Enum\TypeEnum;

/**
 * @property-read string $class_name
 * @property-read string|null $description
 * @property-read array $fields
 * @property-read array $interfaces
 * @property-read string|mixed|null $isTypeOf
 * @property-read string $name
 * @property-read callable|null $parseLiteral
 * @property-read callable|null $parseValue
 * @property-read mixed|null $resolveField
 * @property-read string|null $resolveType
 * @property-read string|mixed|null $scalarType
 * @property-read callable|null $serialize
 * @property-read array|null $types
 * @property-read array|null $validation
 * @property-read array|null $values
 */
final class TypeConfig extends AbstractConfig
{
    private string $type;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config, string $type)
    {
        parent::__construct($config);

        $this->type = $type;
    }

    public function isCustomScalar(): bool
    {
        return TypeEnum::CUSTOM_SCALAR === $this->type;
    }

    public function isInputObject(): bool
    {
        return TypeEnum::INPUT_OBJECT === $this->type;
    }
}
