<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;

class Call extends DependencyAwareGenerator implements \ArrayAccess
{
    private const TYPE_VARIABLE = 0;
    private const TYPE_CLASS = 1;

    private static ?object $lastObject;

    private ?string $identifier;
    private int    $type;

    /** @var GeneratorInterface[] */
    private array $chainParts = [];

    public function generate(): string
    {
        return $this->identifier . implode($this->chainParts);
    }

    public function __call($name, $arguments)
    {
        self::$lastObject->chainParts[] = self::createChainPart($name, $arguments);
        return self::$lastObject;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        self::$lastObject->chainParts[] = self::createChainPart($name, $arguments, true);
        return self::$lastObject;
    }

    public function __invoke(string $identifier): self
    {
        $caller = new self();
        $caller->type = self::resolveType($identifier);
        $caller->identifier = $caller->resolveQualifier($identifier);

        return self::$lastObject = $caller;
    }

    private static function resolveType($identifier)
    {
        return '$' === $identifier[0] ? self::TYPE_VARIABLE : self::TYPE_CLASS;
    }

    /**
     * Shortcut for $this[0]->addArgument()
     */
    public function addArgumentAtFirst($argument)
    {
        $this[0]->addArgument($argument);
    }

    public function offsetExists($offset)
    {
        return isset($this->chainParts[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->chainParts[$offset];
    }

    public function offsetUnset($offset)
    {
        unset($this->chainParts[$offset]);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \Exception
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception("Setting chain parts manually is not allowed.");
    }

    private static function createChainPart(string $name, array $arguments, bool $isStatic = false, bool $isProperty = false): GeneratorInterface
    {
        return new class($name, $arguments, $isStatic, $isProperty) implements GeneratorInterface
        {
            private string $name;
            private array $arguments;
            private bool $isStatic;
            private bool $isProperty;

            public function __construct(string $name, array $arguments, bool $isStatic, bool $isProperty)
            {
                $this->name = $name;
                $this->arguments = $arguments;
                $this->isStatic = $isStatic;
                $this->isProperty = $isProperty;
            }

            public function __toString(): string
            {
                $args = empty($this->arguments) ? '' : implode(', ', $this->arguments);
                
                if ($this->isStatic) {
                    return "::$this->name($args)";
                } else {
                    return "->$this->name($args)";
                }
            }

            public function setArguments(array $arguments): void
            {
                $this->arguments = $arguments;
            }

            public function addArgument($argument): void
            {
                $this->arguments[] = $argument;
            }
        };
    }
}