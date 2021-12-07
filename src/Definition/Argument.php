<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use ReturnTypeWillChange;
use function array_key_exists;
use function count;

class Argument implements ArgumentInterface
{
    private array $rawArguments = [];

    public function __construct(array $rawArguments = null)
    {
        $this->exchangeArray($rawArguments);
    }

    public function exchangeArray(array $array = null): array
    {
        $old = $this->rawArguments;
        $this->rawArguments = $array ?? [];

        return $old;
    }

    public function getArrayCopy(): array
    {
        return $this->rawArguments;
    }

    /**
     * @param int|string $offset
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->rawArguments);
    }

    /**
     * @param int|string $offset
     *
     * @return mixed|null
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        // TODO 1.0: Drop PHP 7.4 support and add mixed return type.

        return $this->offsetExists($offset) ? $this->rawArguments[$offset] : null;
    }

    /**
     * @param int|string $offset
     * @param mixed      $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->rawArguments[$offset] = $value;
    }

    /**
     * @param int|string $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->rawArguments[$offset]);
    }

    public function count(): int
    {
        return count($this->rawArguments);
    }

    public function __get(string $name)
    {
        return $this->rawArguments[$name] ?? null;
    }
}
