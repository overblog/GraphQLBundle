<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Traits;

use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Utils;
use function array_unshift;
use function func_num_args;
use function implode;

trait ScopedContentTrait
{
    private array $content = [];
    private int $emptyLinesBuffer = 0;

    /**
     * Append contents to body.
     *
     * @param GeneratorInterface[]|string[] $values
     * @return self
     */
    public function append(...$values): self
    {
        $valNum = func_num_args() + $this->emptyLinesBuffer;

        if (0 === $valNum) {
            return $this;
        }

        if (1 === $valNum) {
            $this->content[] = $values[0];
        } else {
            $this->content[] = self::createBlock(
                ...array_fill(0, $this->emptyLinesBuffer, "\n"),
                ...$values
            );
            // Reset empty lines buffer
            $this->emptyLinesBuffer = 0;
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

    public function emptyLine()
    {
        $this->emptyLinesBuffer++;
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
