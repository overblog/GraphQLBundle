<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Traits;

use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Utils;
use function array_unshift;

trait ScopedContentTrait
{
    private array $content = [];

    /**
     * @param GeneratorInterface[]|string[] $values
     * @return self
     */
    public function append(...$values): self
    {
        $argNum = func_num_args();

        if ($argNum === 0) {
            return $this;
        }

        if ($argNum === 1) {
            $this->content[] = $values[0];
        } else {
            $this->content[] = self::createBlock(...$values);
        }

        foreach ($values as $value) {
            if ($value instanceof GeneratorInterface) {
                $this->dependencyAwareChildren[] = $value;
            }
        }

        return $this;
    }

    public function prepend(...$values): self
    {
        $argNum = func_num_args();

        if ($argNum === 0) {
            return $this;
        }

        if ($argNum === 1) {
            array_unshift($this->content, $values[0]);
        } else {
            array_unshift($this->content, self::createBlock(...$values));
        }

        foreach ($values as $value) {
            if ($value instanceof GeneratorInterface) {
                $this->dependencyAwareChildren[] = $value;
            }
        }

        return $this;
    }

    private function generateContent(): string
    {
        $content = '';

        if (!empty($this->content)) {
            $content = Utils::indent(implode(";\n", $this->content).';');
        }

        return $content;
    }

    private static function createBlock(...$parts)
    {
        return new class(...$parts) implements GeneratorInterface
        {
            public array $parts;

            public function __construct(...$args)
            {
                $this->parts = $args;
            }

            public function __toString(): string
            {
                return implode($this->parts);
            }
        };
    }
}