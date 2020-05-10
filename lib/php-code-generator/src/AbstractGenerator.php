<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;

abstract class AbstractGenerator implements GeneratorInterface
{
    abstract public function generate(): string;

    public function __toString(): string
    {
        return $this->generate();
    }
}