<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use function count;
use function sprintf;
use function trigger_error;
use const E_USER_DEPRECATED;

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
     * @deprecated This method is deprecated since 0.12 and will be removed in 0.13. You should use getArrayCopy method instead.
     */
    public function getRawArguments(): array
    {
        @trigger_error(
            sprintf(
                'This "%s" method is deprecated since 0.12 and will be removed in 0.13. You should use "%s::getArrayCopy" instead.',
                __METHOD__,
                __CLASS__
            ),
            E_USER_DEPRECATED
        );

        return $this->getArrayCopy();
    }

    /**
     * @param int|string $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->rawArguments[$offset]);
    }

    /**
     * @param int|string $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
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
}
