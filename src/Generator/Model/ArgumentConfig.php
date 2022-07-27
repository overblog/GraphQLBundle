<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Model;

/**
 * @property-read mixed|null $defaultValue
 * @property-read string|null $description
 * @property-read string $type
 * @property-read array|null $validation
 */
final class ArgumentConfig extends AbstractConfig
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
