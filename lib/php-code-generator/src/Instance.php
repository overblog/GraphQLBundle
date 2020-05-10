<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;

class Instance extends DependencyAwareGenerator
{
    private array $args = [];
    private string $qualifier;

    public bool $multiline = false;
    public bool $skipNullArgs = true;

    public function __construct(string $qualifier, ...$args)
    {
        $this->qualifier = $this->resolveQualifier($qualifier);
        $this->args = $args;
    }

    public function acceptNullArgs(): self
    {
        $this->skipNullArgs = false;
        return $this;
    }

    public function skipNullArgs(): self
    {
        $this->skipNullArgs = true;
        return $this;
    }

    /**
     * @return string
     * @throws Exception\UnrecognizedValueTypeException
     */
    public function generate(): string
    {
        if (empty($this->args)) {
            return "new $this->qualifier()";
        }

        if ($this->multiline) {
            $args = "\n";
            $suffix = ",\n";
        } else {
            $args = '';
            $suffix = ", ";
        }

        foreach ($this->args as $arg) {
            $args .= Utils::stringify($arg).$suffix;
        }

        if ($this->multiline) {
            $args = Utils::indent($args);
        }

        $args = rtrim($args, ', ');

        return "new $this->qualifier($args)";
    }

    public function addArgument($arg): self
    {
        $this->args[] = $arg;
        return $this;
    }

    public static function createMultiline(string $qualifier, ...$args)
    {
        $instance = new self($qualifier, ...$args);
        $instance->multiline = true;

        return $instance;
    }

    public static function create(string $qualifier, ...$args)
    {
        return new self($qualifier, ...$args);
    }
}