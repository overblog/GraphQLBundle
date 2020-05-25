<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Functions;

use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\Utils;

class Argument extends DependencyAwareGenerator
{
    private string  $type;
    private string  $name;
    private bool    $isSpread = false;
    private bool    $isByReference = false;

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
    public function __construct(string $name, string $type = '', $defaultValue = null)
    {
        $this->name = $name;
        $this->type = $this->resolveQualifier($type);

        if (func_num_args() > 2) {
            $this->setDefaultValue($defaultValue);
        }
    }

    public static function create(string $name, string $type = '', $defaultValue = null): self
    {
        if (func_num_args() === 2) {
            return new self($name, $type);
        } else {
            return new self($name, $type, $defaultValue);
        }
    }

    public function generate(): string
    {
        $code = '';

        if ($this->type) {
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
}
