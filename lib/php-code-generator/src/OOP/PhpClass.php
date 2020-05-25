<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\OOP;

class PhpClass extends OOPStructure
{
    private bool $isFinal = false;
    private bool $isAbstract = false;

    public function __construct(string $name)
    {
        $this->dependencyAwareChildren = [&$this->methods, &$this->props];

        parent::__construct($name);
    }

    public function generate(): string
    {
        return <<<CODE
        {$this->buildDocBlock()}{$this->buildPrefix()}class $this->name {$this->buildExtends()}{$this->buildImplements()}
        {
        {$this->buildContent()}
        }
        CODE;
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    private function buildPrefix(): string
    {
        $prefix = '';

        if ($this->isFinal) {
            $prefix .= 'final ';
        } elseif ($this->isAbstract) {
            $prefix .= 'abstract ';
        }

        return $prefix;
    }

    private function buildDocBlock()
    {
        return $this->docBlock ? "$this->docBlock\n" : '';
    }

    public function isFinal(): bool
    {
        return $this->isFinal;
    }

    public function setFinal(): self
    {
        $this->isFinal = true;

        // Class cannot be final and abstract at the same time
        $this->isAbstract = false;

        return $this;
    }

    public function unsetFinal(): self
    {
        $this->isFinal = false;
        return $this;
    }

    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    public function setAbstract(): PhpClass
    {
        $this->isAbstract = true;

        // Class cannot be final and abstract at the same time
        $this->isFinal = false;
        return $this;
    }

    public function unsetAbstract(): PhpClass
    {
        $this->isAbstract = false;
        return $this;
    }
}
