<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\Functions;

use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\Traits\FunctionTrait;
use Murtukov\PHPCodeGenerator\Traits\ScopedContentTrait;

class Closure extends DependencyAwareGenerator
{
    use FunctionTrait;
    use ScopedContentTrait;

    private array $uses = []; // variables of parent scope

    public function __construct()
    {
        $this->dependencyAwareChildren = [&$this->args];
    }

    public static function new()
    {
        return new self();
    }

    public function generate(): string
    {
        return <<<CODE
        function ({$this->generateArgs()}){$this->buildUses()}{$this->buildReturnType()} {
        {$this->generateContent()}
        }
        CODE;
    }

    private function buildUses(): string
    {
        if (!empty($this->uses)) {
            return ' use (' .implode(', ', $this->uses). ')';
        }

        return '';
    }

    private function buildReturnType()
    {
        return $this->returnType ? ": $this->returnType" : '';
    }

    public function bindVar(string $name, bool $isByReference = false): self
    {
        $this->uses[] = $isByReference ? "&$$name" : "$$name";
        return $this;
    }

    /**
     * Remove all use-variables
     */
    public function removeUses()
    {
        $this->uses = [];
        return $this;
    }
}
