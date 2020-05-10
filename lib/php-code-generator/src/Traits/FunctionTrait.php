<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Traits;

use Murtukov\PHPCodeGenerator\Functions\Argument;

trait FunctionTrait
{
    protected string  $returnType = '';
    protected array   $args = [];


    protected function generateArgs(): string
    {
        return \implode(", ", $this->args);
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function setReturnType(string $returnType): self
    {
        $this->returnType = $this->resolveQualifier($returnType);
        return $this;
    }

    public function getArguments(): array
    {
        return $this->args;
    }

    public function removeArgumentAt(int $index): self
    {
        unset($this->args[$index]);
        return $this;
    }

    public function createArgument(string $name, string $type = '', $defaultValue = ''): Argument
    {
        return $this->args[] = new Argument($name, $type, $defaultValue);
    }

    public function addArgument(Argument $argument): self
    {
        $this->args[] = $argument;

        return $this;
    }
}