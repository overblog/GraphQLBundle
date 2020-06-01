<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Definition\Type\CustomScalarType;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;

class CustomScalarTypeBuilder extends BaseBuilder
{
    public function build(array $config): PhpFile
    {
        $name = $config['name'];
        $this->config = $config;

        $this->file = PhpFile::create($name.'Type.php')->setNamespace($this->namespace);

        $class = $this->file->createClass($name.'Type')
            ->setFinal()
            ->setExtends(CustomScalarType::class)
            ->addImplements(GeneratedTypeInterface::class)
            ->addConst('NAME', $name)
            ->addDocBlock(self::DOCBLOCK_TEXT);

        $class->createConstructor()
            ->addArgument('configProcessor', ConfigProcessor::class)
            ->addArgument('globalVariables', GlobalVariables::class, null)
            ->append('$configLoader = ', $this->buildConfigLoader($config))
            ->append('$config = $configProcessor->process(LazyConfig::create($configLoader, $globalVariables))->load()')
            ->append('parent::__construct($config)');

        $this->file->addUse(LazyConfig::class);

        return $this->file;
    }
}
