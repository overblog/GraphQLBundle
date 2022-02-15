<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Config;

use InvalidArgumentException;

/**
 * @internal
 */
abstract class AbstractConfig
{
    protected const NORMALIZERS = [];

    public function __construct(array $config)
    {
        $this->populate($config);
    }

    protected function populate(array $config): void
    {
        foreach ($config as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords($key, '_')));
            $normalizer = static::NORMALIZERS[$property] ?? 'normalize'.ucfirst($property);
            if (method_exists($this, $normalizer)) {
                $this->$property = $this->$normalizer($value);
            } elseif (property_exists($this, $property)) {
                $this->$property = $value;
            } else {
                throw new InvalidArgumentException(sprintf('Unknown config "%s".', $property));
            }
        }
    }

    /**
     * @param array|mixed $value
     */
    protected function normalizeCallback($value): Callback
    {
        return new Callback($value);
    }

    protected function normalizeValidation(array $config): Validation
    {
        return new Validation($config);
    }
}
