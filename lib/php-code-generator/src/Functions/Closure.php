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
        if (!empty($this->uses) > 0) {
            $last = array_key_last($this->uses);

            $code = '';
            foreach ($this->uses as $key => $var) {
                $code .= "$$var";

                if ($key !== $last) {
                    $code .= ', ';
                }
            }

            return " use ($code)";
        }

        return '';
    }

    private function buildReturnType()
    {
        return $this->returnType ? ": $this->returnType" : '';
    }

    public function __toString(): string
    {
        return $this->generate();
    }
}
