<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\ControlStructures;

use Murtukov\PHPCodeGenerator\AbstractGenerator;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Traits\ScopedContentTrait;

class IfElse extends AbstractGenerator
{
    use ScopedContentTrait;

    /** @var GeneratorInterface|string */
    private $expression;

    /** @var GeneratorInterface[]  */
    private array $elseIfBlocks = [];

    private ?GeneratorInterface $elseBlock = null;

    /**
     * @param GeneratorInterface|string $ifExpression
     */
    public function __construct($ifExpression = '')
    {
        $this->expression = $ifExpression;
    }

    public static function create($ifExpression = ''): self
    {
        return new self($ifExpression);
    }

    /**
     * @param GeneratorInterface|string $expression
     * @return IfElse
     */
    public function setExpression($expression): self
    {
        $this->expression = $expression;
        return $this;
    }

    /**
     * Just a mock to be consistent with 'else' blocks
     */
    public function end()
    {
        return $this;
    }

    public function generate(): string
    {
        $elseIfBlocks = implode($this->elseIfBlocks);

        return <<<CODE
        if ($this->expression) {
        {$this->generateContent()}  
        }{$elseIfBlocks}$this->elseBlock
        CODE;
    }

    /**
     * @param GeneratorInterface|string $expression
     * @return IfElse
     */
    public function createElseIf($expression): object
    {
        return $this->elseIfBlocks[] = new class($expression, $this) implements GeneratorInterface
        {
            use ScopedContentTrait;

            /** @var GeneratorInterface|string */
            public $expression;

            public IfElse $parent;

            public function __construct($expression, $parent)
            {
                $this->expression = $expression;
                $this->parent = $parent;
            }

            function __toString(): string
            {
                if (empty($this->expression)) {
                    return '';
                }
                return " elseif ($this->expression) {\n{$this->generateContent()}\n}";
            }

            public function end()
            {
                return $this->parent;
            }
        };
    }

    /**
     * @return $this
     */
    public function createElse(): object
    {
        return $this->elseBlock = new class($this) implements GeneratorInterface
        {
            use ScopedContentTrait;

            public IfElse $parent;

            public function __construct($parent)
            {
                $this->parent = $parent;
            }

            public function __toString(): string
            {
                return " else {\n{$this->generateContent()}\n}";
            }

            public function end()
            {
                return $this->parent;
            }
        };
    }
}