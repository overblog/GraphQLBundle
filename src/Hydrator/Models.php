<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

/**
 * Container for all already hydrated models.
 */
class Models
{
    private array $models;

    public function __construct(array $models)
    {
        foreach ($models as $name => $model) {
            $this->$name = $model;
        }

        $this->models = $models;
    }

    public function get(string $name)
    {
        return $this->$name ?? null;
    }

    public function getAll(): array
    {
        return $this->models;
    }
}
