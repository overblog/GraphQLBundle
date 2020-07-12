<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

/**
 * Container for all already hydrated models.
 */
class Models
{
    public array $models = [];

    public function get(string $name)
    {
        return $this->$name ?? null;
    }

    public function getAll(): array
    {
        return $this->models;
    }

    public function add(string $name, $model)
    {
        $this->models[$name] = $model;
    }

    public function __get($name)
    {
        return $this->models[$name] ?? null;
    }
}
