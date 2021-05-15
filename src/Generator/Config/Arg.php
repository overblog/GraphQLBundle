<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Config;

final class Arg extends AbstractConfig
{
    public string $type;
    public ?string $description = null;
    public ?Validation $validation = null;

    /**
     * @var mixed
     */
    public $defaultValue;

    public bool $hasDefaultValue;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->hasDefaultValue = array_key_exists('defaultValue', $config);
    }
}
