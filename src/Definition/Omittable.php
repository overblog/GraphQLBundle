<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition;

use LogicException;

/**
 * @template T
 */
final class Omittable
{
    /**
     * @param T|null $value
     */
    private function __construct(
        private readonly bool $isSet,
        private readonly mixed $value = null,
    ) {
    }

    /**
     * @return self<T>
     */
    public static function omitted(): self
    {
        /** @var self<T> $omitted */
        $omitted = new self(false);

        return $omitted;
    }

    /**
     * @param T $value
     *
     * @return self<T>
     */
    public static function set(mixed $value): self
    {
        return new self(true, $value);
    }

    public function isSet(): bool
    {
        return $this->isSet;
    }

    /**
     * @return T
     */
    public function value(): mixed
    {
        if (!$this->isSet) {
            throw new LogicException('Cannot read the value of an omitted input field.');
        }

        return $this->value;
    }
}
