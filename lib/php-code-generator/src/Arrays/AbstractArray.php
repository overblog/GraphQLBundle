<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Arrays;

use Closure;
use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Mock;
use function count;
use function is_bool;
use function is_callable;

abstract class AbstractArray extends DependencyAwareGenerator
{
    protected bool  $multiline = false;
    protected array $items = [];

    public function __construct(array $items = [], bool $multiline = false)
    {
        $this->items = $items;
        $this->multiline = $multiline;
    }

    public static function new(array $items = [], bool $multiline = false): self
    {
        return new static($items, $multiline);
    }

    /**
     * Shorthand for `new AssocArray($items, true)`
     *
     * @param GeneratorInterface[]|string[] $items
     * @return AbstractArray
     */
    public static function multiline(array $items = []): self
    {
        return new static($items, true);
    }

    public static function map(array $items, callable $map): self
    {
        $array = new static();
        $array->multiline = true;

        foreach ($items as $key => $value) {
            $array->addItem($key, $map($value, $key));
        }

        return $array;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return $this
     */
    public function addItem(string $key, $value): self
    {
        $this->items[$key] = $value;

        if ($value instanceof DependencyAwareGenerator) {
            $this->dependencyAwareChildren[] = $value;
        }

        return $this;
    }

    public function addIfNotNull(string $key, $value): self
    {
        if (null === $value) {
            return $this;
        }

        return $this->addItem($key, $value);
    }

    public function addIfNotEmpty(string $key, $value)
    {
        if (empty($value)) {
            return $this;
        }

        return $this->addItem($key, $value);
    }

    public function addIfNotFalse(string $key, $value)
    {
        if (!$value) {
            return $this;
        }

        return $this->addItem($key, $value);
    }

    /**
     * @param mixed $value
     * @return self|Mock
     */
    public function ifNotNull($value)
    {
        if (null !== $value) {
            return $this;
        }
        return Mock::getInstance($this);
    }

    /**
     * @param bool|Closure $value
     * @return self|Mock
     */
    public function ifTrue($value)
    {
        if (is_bool($value)) {
            return $value ? $this : Mock::getInstance($this);
        } elseif (is_callable($value)) {
            return $value() ? $this : Mock::getInstance($this);
        }

        return Mock::getInstance($this);
    }

    /**
     * @param mixed $value
     * @return self|Mock
     */
    public function ifNotEmpty($value)
    {
        return !empty($value) ? $this : Mock::getInstance($this);
    }

    public static function getConverters()
    {
        return [];
    }

    public function setMultiline(): self
    {
        $this->multiline = true;
        return $this;
    }

    public function unsetMultiline(): self
    {
        $this->multiline = false;
        return $this;
    }

    public function count()
    {
        return count($this->items);
    }

    /**
     * @return GeneratorInterface|string|null
     */
    public function getFirstItem()
    {
        return $this->items[0] ?? null;
    }
}
