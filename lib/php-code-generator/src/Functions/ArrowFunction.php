<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Functions;

use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Traits\FunctionTrait;

class ArrowFunction extends DependencyAwareGenerator
{
    use FunctionTrait;

    /** @var GeneratorInterface|string  */
    private $expression;

    public function __construct($expression = '', string $returnType = '')
    {
        $this->expression = $this->manageDependency($expression);
        $this->returnType = $returnType;

        $this->dependencyAwareChildren[] = $this->args;
    }

    public static function create($expression = '', string $returnType = '')
    {
        return new self($expression, $returnType);
    }

    public function generate(): string
    {
        return "fn({$this->generateArgs()}) => $this->expression";
    }

    /**
     * @return GeneratorInterface|string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @param GeneratorInterface|string $expression
     * @return self
     */
    public function setExpression($expression): self
    {
        $this->expression = $this->manageDependency($expression);
        return $this;
    }

    protected function manageDependency($value)
    {
        if ($value instanceof DependencyAwareGenerator) {
            $this->dependencyAwareChildren['expr'] = $value;
        } elseif (is_string($value)) {
            unset($this->dependencyAwareChildren['expr']);
        }

        return $value;
    }
}