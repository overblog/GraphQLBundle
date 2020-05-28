<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Functions;

use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\Utils;

class Argument extends DependencyAwareGenerator implements FunctionMemberInterface
{
    /**
     * Special value to represent that there is no argument passed
     */
    public const NO_PARAM = INF;

    private string  $type;
    private string  $name;
    private bool    $isSpread = false;
    private bool    $isByReference = false;
    private bool    $isNullable = false;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * Argument constructor.
     * @param string $name
     * @param string $type
     * @param mixed $defaultValue
     */
    public function __construct(string $name, string $type = '', $defaultValue = self::NO_PARAM)
    {
        $this->name = $name;
        $this->type = $this->resolveQualifier($type);

        if (INF !== $defaultValue) {
            $this->setDefaultValue($defaultValue);
        }
    }

    public static function new(string $name, string $type = '', $defaultValue = self::NO_PARAM): self
    {
        return new self($name, $type, $defaultValue);
    }

    public function generate(): string
    {
        $code = '';

        if ($this->type) {
            if ($this->isNullable && '?' !== $this->type[0]) {
                $code .= '?';
            }
            $code .= $this->type . ' ';
        }
        if ($this->isByReference) {
            $code .= '&';
        }
        if ($this->isSpread) {
            $code .= '...';
        }

        $code .= '$' . $this->name;

        if ($this->defaultValue) {
            $code .= " = $this->defaultValue";
        }

        return $code;
    }

    public function __toString(): string
    {
        return $this->generate();
    }

    public function isSpread(): bool
    {
        return $this->isSpread;
    }

    public function setIsSpread(bool $isSpread): self
    {
        $this->isSpread = $isSpread;
        return $this;
    }

    public function isByReference(): bool
    {
        return $this->isByReference;
    }

    public function setIsByReference(bool $isByReference): self
    {
        $this->isByReference = $isByReference;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setDefaultValue($value): self
    {
        $this->defaultValue = Utils::stringify($value);

        return $this;
    }

    public function unsetNullable(): self
    {
        $this->isNullable = false;
        return $this;
    }

    public function setNullable(): self
    {
        $this->isNullable = true;
        return $this;
    }
}
