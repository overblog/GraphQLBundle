<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Config;

final class Field extends AbstractConfig
{
    protected const NORMALIZERS = [
        'resolver' => 'normalizeCallback',
//        'access' => 'normalizeCallback',
//        'public' => 'normalizeCallback',
//        'complexity' => 'normalizeCallback',
    ];

    public string $type;
    public ?string $description = null;
    /** @var Arg[]|null */
    public ?array $args = null;
    public ?Callback $resolver = null;
/** @var mixed|null */
    /*?Callback*/ public $access = null;
/** @var mixed|null */
    /*?Callback*/ public $public = null;
/** @var mixed|null */
    /*?Callback*/ public $complexity = null;
    public ?Validation $validation = null;
    public ?array $validationGroups = null;
    public ?string $deprecationReason = null;

    /**
     * @var mixed
     */
    public $defaultValue;

    public bool $hasDefaultValue;
    public bool $hasOnlyType;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->hasOnlyType = 1 === count($config) && isset($config['type']);
        $this->hasDefaultValue = array_key_exists('defaultValue', $config);
    }

    protected function normalizeArgs(array $args): array
    {
        return array_map(fn (array $arg) => new Arg($arg), $args);
    }
}
