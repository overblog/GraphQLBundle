<?php

namespace Murtukov\PHPCodeGenerator\OOP;

use Murtukov\PHPCodeGenerator\Comments\DocBlock;
use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\Utils;

class Property extends DependencyAwareGenerator
{
    public const PRIVATE = 'private';
    public const PROTECTED = 'protected';
    public const PUBLIC = 'public';

    public string $name;
    public ?DocBlock $docBlock = null;

    private string $modifier;
    private string $defaulValue = '';
    private bool   $isStatic = false;
    private bool   $isConst = false;
    private string $type;

    public function __construct(string $name, ?string $modifier, ?string $type, ?string $defaulValue)
    {
        $this->name = $name;
        $this->modifier = $modifier ?? self::PUBLIC;
        $this->defaulValue = $defaulValue ? Utils::stringify($defaulValue) : '';
        $this->type = $type ?? '';
    }

    public function generate(): string
    {
        $docBlock = $this->docBlock ? "$this->docBlock\n" : '';
        $type = $this->type ? $this->type.' ' : '';
        $value = $this->defaulValue ? " = $this->defaulValue" : '';
        $isStatic = $this->isStatic ? 'static ' : '';

        if ($this->isConst) {
            return "$docBlock$this->modifier const $this->name$value;";
        }

        return "$docBlock$this->modifier $isStatic$type$$this->name$value;";
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getModifier(): string
    {
        return $this->modifier;
    }

    public function getDefaulValue(): string
    {
        return $this->defaulValue;
    }

    public function setDefaulValue($defaulValue): self
    {
        $this->defaulValue = Utils::stringify($defaulValue);
        return $this;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function setStatic(): self
    {
        $this->isStatic = true;
        $this->isConst = false;
        return $this;
    }

    public function setConst(): self
    {
        $this->isConst = true;
        $this->isStatic = false;
        return $this;
    }

    function setPublic(): self
    {
        $this->modifier = 'public';
        return $this;
    }

    function setPrivate(): self
    {
        $this->modifier = 'private';
        return $this;
    }

    function setProtected(): self
    {
        $this->modifier = 'protected';
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $this->resolveQualifier($type);
        return $this;
    }
}