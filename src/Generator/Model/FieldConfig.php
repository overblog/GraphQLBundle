<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Model;

/**
 * @property-read array|null $args
 * @property-read mixed|null $access
 * @property-read string|null $complexity
 * @property-read string|null $defaultValue
 * @property-read string|null $deprecatedReason
 * @property-read string|null $description
 * @property-read string|null $deprecationReason
 * @property-read string|null $public
 * @property-read string|null $resolve
 * @property-read string $type
 * @property-read array|null $validation
 * @property-read array|null $validationGroups
 */
final class FieldConfig extends AbstractConfig
{
    private string $name;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config, string $name)
    {
        parent::__construct($config);

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
