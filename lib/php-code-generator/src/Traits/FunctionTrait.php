<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Traits;

use Murtukov\PHPCodeGenerator\Functions\Argument;
use Murtukov\PHPCodeGenerator\Functions\FunctionMemberInterface;

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

    /**
     * Some arguments are stored as simple strings for performance.
     * If they are requested, they are first converted into objects
     * then returned back.
     *
     * @param int $index
     * @return Argument
     */
    public function getArgument(int $index): ?Argument
    {
        if (isset($this->args[$index])) {
            $arg = $this->args[$index];

            if (is_string($arg)) {
                return $this->args[$index] = new Argument($arg);
            }

            return $arg;
        }

        return null;
    }

    public function removeArgument(int $index): self
    {
        unset($this->args[$index]);
        return $this;
    }

    public function createArgument(string $name, string $type = '', $defaultValue = Argument::NO_PARAM): Argument
    {
        return $this->args[] = new Argument($name, $type, $defaultValue);
    }

    public function addArgument(string $name, string $type = '', $defaultValue = Argument::NO_PARAM): self
    {
        if (func_num_args() === 1) {
            $this->args[] = "$$name";
        } else {
            $this->args[] = new Argument($name, $type, $defaultValue);
        }

        return $this;
    }

    public function add(FunctionMemberInterface $member): self
    {
        if ($member instanceof Argument) {
            $this->args[] = $member;
        }

        return $this;
    }
}
