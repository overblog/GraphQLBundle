<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use LogicException;
use function json_encode;
use function sprintf;

final class GlobalVariables
{
    private array $services;

    public function __construct(array $services = [])
    {
        $this->services = $services;
    }

    /**
     * @return mixed
     */
    public function get(string $name)
    {
        if (!isset($this->services[$name])) {
            throw new LogicException(sprintf('Global variable %s could not be located. You should define it.', json_encode($name)));
        }

        return $this->services[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }
}
