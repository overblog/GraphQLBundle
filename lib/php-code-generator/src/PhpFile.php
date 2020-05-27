<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;

use Murtukov\PHPCodeGenerator\OOP\PhpClass;
use function dirname;
use function file_put_contents;
use function implode;
use function is_int;
use function ksort;
use function mkdir;

class PhpFile extends DependencyAwareGenerator
{
    /** @var PhpClass[]  */
    private array $classes = [];

    /** @var string[] */
    private array $declares;

    protected string $namespace = '';
    private   string $name;

    public function __construct(string $name = '')
    {
        $this->name = $name;
        $this->dependencyAwareChildren = [&$this->classes];
    }

    public static function create(string $name = ''): self
    {
        return new self($name);
    }

    public function generate(): string
    {
        $namespace = $this->namespace ? "\nnamespace $this->namespace;\n" : '';
        $classes = implode("\n\n", $this->classes);

        return <<<CODE
        <?php
        $namespace{$this->buildUseStatements()}
        $classes
        CODE;
    }

    public function __toString(): string
    {
        return $this->generate();
    }

    public function addClass(PhpClass $class): self
    {
        $this->classes[] = $class;

        return $this;
    }

    public function createClass(string $name): PhpClass
    {
        return $this->classes[] = new PhpClass($name);
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function addUseStatement(string $fqcn, string $alias = ''): self
    {
        $this->usePaths[$fqcn] = $alias;

        return $this;
    }

    public function addUseStatements(array $paths)
    {
        foreach ($paths as $key => $value) {
            if (is_int($key)) {
                $this->usePaths[$value] = '';
            } else {
                $this->usePaths[$key] = $value;
            }
        }
    }

    public function buildUseStatements(): string
    {
        $code = '';

        $paths = $this->getUsePaths();

        if (empty($paths)) {
            return $code;
        }

        if (!empty(ksort($paths))) {
            $code = "\n";

            foreach ($paths as $path => $alias) {
                $code .= "use $path";

                if ($alias) {
                    $code .= " as $alias";
                }

                $code .= ";\n";
            }
        }

        return $code;
    }


    public function save(string $path)
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!file_exists($path)) {
            file_put_contents($path, $this);
        }
    }
}
