<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Config;

/**
 * @internal
 */
final class Config extends AbstractConfig
{
    protected const NORMALIZERS = [
        'fieldResolver' => 'normalizeCallback',
        'typeResolver' => 'normalizeCallback',
//        'fieldsDefaultAccess' => 'normalizeCallback',
//        'isTypeOf' => 'normalizeCallback',
//        'fieldsDefaultPublic' => 'normalizeCallback',
    ];

    public string $name;
    public ?string $description = null;
    public string $className;
    /** @var Field[]|null */
    public ?array $fields = null;
    public ?array $interfaces = null;
    public ?Callback $fieldResolver = null;
    public ?Callback $typeResolver = null;
    public ?Validation $validation = null;
    public ?array $builders = null;
    public ?array $types = null;
    public ?array $values = null;
/** @var mixed|null */
    /*?Callback*/ public $fieldsDefaultAccess = null;
/** @var mixed|null */
    /*?Callback*/ public $isTypeOf = null;
/** @var mixed|null */
    /*?Callback*/ public $fieldsDefaultPublic = null;
    public ?string $scalarType = null;
    /** @var callable|null */
    public $serialize = null;
    /** @var callable|null */
    public $parseValue = null;
    /** @var callable|null */
    public $parseLiteral = null;

    protected function normalizeFields(array $fields): array
    {
        return array_map(fn (array $field) => new Field($field), $fields);
    }
}
