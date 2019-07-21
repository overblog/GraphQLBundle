<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

class Argument implements ArgumentInterface
{
    /** @var array */
    private $rawArguments = [];

    public function __construct(?array $rawArguments = null)
    {
        $this->exchangeArray($rawArguments);
    }

    public function exchangeArray($array): array
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
     * @return array
     *
     * @deprecated This method is deprecated since 0.12 and will be removed in 0.13. You should use getArrayCopy method instead.
     */
    public function getRawArguments(): array
    {
        @\trigger_error(
            \sprintf(
                'This "%s" method is deprecated since 0.12 and will be removed in 0.13. You should use "%s::getArrayCopy" instead.',
                __METHOD__,
                __CLASS__
            ),
            \E_USER_DEPRECATED
        );

        return $this->getArrayCopy();
    }

    public function offsetExists($offset)
    {
        return \array_key_exists($offset, $this->rawArguments);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->rawArguments[$offset] : null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->rawArguments[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->rawArguments[$offset]);
    }

    public function count()
    {
        return \count($this->rawArguments);
    }
}
