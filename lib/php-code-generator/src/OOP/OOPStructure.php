<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator\OOP;

use Murtukov\PHPCodeGenerator\Comments\DocBlock;
use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\Functions\Method;
use Murtukov\PHPCodeGenerator\Utils;

abstract class OOPStructure extends DependencyAwareGenerator
{
    /** @var Method[] */
    protected array $methods = [];

    /** @var string[] */
    protected array $implements = [];

    /** @var array[] */
    protected array $props = [];

    protected ?DocBlock $docBlock = null;
    protected string   $extends = '';
    protected string   $name;


    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setExtends(string $fqcn): self
    {
        $this->extends = $this->resolveQualifier($fqcn);

        return $this;
    }

    /**
     * @param string ...$classNames
     * @return $this
     */
    public function addImplements(string ...$classNames): self
    {
        foreach ($classNames as $name) {
            $this->implements[] = $this->resolveQualifier($name);
        }

        return $this;
    }

    protected function buildImplements(): string
    {
        return count($this->implements) > 0 ? 'implements ' . implode(', ', $this->implements) : '';
    }

    protected function buildExtends()
    {
        if ($this->extends) {
            return "extends $this->extends ";
        }

        return '';
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function createProperty(string $name, string $modifier = Property::PUBLIC, string $type = '', string $defaultValue = ''): Property
    {
        return $this->props[] = new Property($name, $modifier, $type, $defaultValue);
    }

    public function createConst(string $name, string $value, string $modifier = 'public'): Property
    {
        return $this->createProperty($name, $modifier, '', $value)->setConst();
    }

    public function addConst(string $name, string $value, string $modifier = 'public'): self
    {
        $this->createProperty($name, $modifier, '', $value)->setConst();
        return $this;
    }

    public function addProperty(Property $property)
    {
        $this->props[] = $property;
        return $this;
    }

    public function createMethod(string $name, string $modifier = 'public', string $returnType = ''): Method
    {
        return $this->methods[] = new Method($name, $modifier, $returnType);
    }

    public function addMethod(Method $method): self
    {
        $this->methods[] = $method;
        return $this;
    }

    public function createConstructor(string $modifier = 'public'): Method
    {
        return $this->methods[] = new Method('__construct', $modifier, '');
    }

    protected function buildContent(): string
    {
        $code = implode("\n", $this->props);

        if (!empty($code)) {
            $code .= "\n\n";
        }

        $code .= implode("\n\n", $this->methods);

        return Utils::indent($code);
    }

    public function getDocBlock(): DocBlock
    {
        return $this->docBlock;
    }

    public function setDocBlock(DocBlock $docBlock): self
    {
        $this->docBlock = $docBlock;
        return $this;
    }

    public function createDocBlock(string $text): DocBlock
    {
        return $this->docBlock = new DocBlock($text);
    }

    public function addDocBlock(string $text): self
    {
        $this->docBlock = new DocBlock($text);
        return $this;
    }
}
