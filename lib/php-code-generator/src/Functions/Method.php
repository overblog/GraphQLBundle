<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Functions;

use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\Traits\FunctionTrait;
use Murtukov\PHPCodeGenerator\Traits\ScopedContentTrait;

class Method extends DependencyAwareGenerator
{
    use ScopedContentTrait;
    use FunctionTrait;

    const PUBLIC = 'public';
    const PROTECTED = 'protected';
    const PRIVATE = 'private';

    private string  $name;
    private string  $modifier;
    private array   $customStack = [];
    public  bool    $isStatic = false;

    public static function create(string $name, string $modifier = self::PUBLIC, string $returnType = ''): self
    {
        return new self($name, $modifier, $returnType);
    }

    public static function createConstructor(string $modifier = self::PUBLIC): self
    {
        return new self('__construct', $modifier, '');
    }

    public function __construct(string $name, string $modifier = self::PUBLIC, string $returnType = '')
    {
        $this->name = $name;
        $this->modifier = $modifier;
        $this->returnType = $returnType;

        $this->dependencyAwareChildren = [&$this->args];
    }

    public function generate(): string
    {
        $isStatic = $this->isStatic ? 'static ' : '';
        $args = \implode(", ", $this->args);

        $signature = "$this->modifier {$isStatic}function $this->name($args)";

        if ($this->returnType) {
            $signature .= ": $this->returnType";
        }

        return <<<CODE
        $signature
        {
        {$this->generateContent()}
        }
        CODE;
    }

    public function __toString(): string
    {
        return $this->generate();
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function setReturnType(string $returnType): Method
    {
        $this->returnType = $returnType;
        return $this;
    }

    public function shortenQulifiers(bool $value): self
    {
        $this->shortenQualifiers = $value;

        return $this;
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public function setStatic(): self
    {
        $this->isStatic = true;
        return $this;
    }

    public function unsetStatic()
    {
        $this->isStatic = false;
        return $this;
    }
}
