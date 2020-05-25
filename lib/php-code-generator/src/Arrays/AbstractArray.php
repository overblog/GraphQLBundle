<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Arrays;

use Closure;
use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Mock;

abstract class AbstractArray extends DependencyAwareGenerator
{
    protected bool  $multiline = false;
    protected array $items = [];
    protected bool  $isMap = false;

    /** @var callable */
    protected $map;

    public function __construct(array $items = [], bool $multiline = false)
    {
        $this->items = $items;
        $this->multiline = $multiline;
    }

    public static function create(array $items = [], bool $multiline = false): self
    {
        return new static($items, $multiline);
    }

    /**
     * Shorthand for new AssocArray([], true)
     *
     * @param GeneratorInterface[]|string[] $items
     * @return AbstractArray
     */
    public static function createMultiline(array $items = []): self
    {
        return new static($items, true);
    }

    public static function mapMultiline(array $items, callable $map): self
    {
        $array = new static();
        $array->map = $map;
        $array->isMap = true;
        $array->multiline = true;

        foreach ($items as $key => $value) {
            $array->items[$key] = ($array->map)($value, $key);

            if ($array->items[$key] instanceof DependencyAwareGenerator) {
                $array->dependencyAwareChildren[] = $array->items[$key];
            }
        }

        return $array;
    }

    public static function mapInline(array $items, callable $map)
    {
        $array = new static();
        $array->items = $items;
        $array->map = $map;
        $array->isMap = true;

        return $array;
    }

    /**
     * @param string $key
     * @param string|GeneratorInterface $value
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

    public static function getStringifiers()
    {
        return [];
    }
}
