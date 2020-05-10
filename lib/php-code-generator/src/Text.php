<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;

class Text extends AbstractGenerator
{
    public string $value;
    public bool $doubleQuotes;

    public function __construct(string $value, bool $doubleQuotes = false)
    {
        $this->value = $value;
        $this->doubleQuotes = $doubleQuotes;
    }

    public static function create(string $value, bool $doubleQuotes = false): self
    {
        return new self($value, $doubleQuotes);
    }

    public function generate(): string
    {
        if ($this->doubleQuotes) {
            return '"'.$this->value.'"';
        } else {
            return "'$this->value'";
        }
    }
}