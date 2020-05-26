<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use GraphQL\Type\Definition\InterfaceType;
use Murtukov\PHPCodeGenerator\Functions\Argument;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;

class InterfaceTypeBuilder extends BaseBuilder
{
    public function build(array $config): GeneratorInterface
    {
        $name = $config['name'];

        $file = PhpFile::create($name.'Type.php')->setNamespace($this->namespace);

        $class = $file->createClass($name.'Type')
            ->setFinal()
            ->setExtends(InterfaceType::class)
            ->addImplement(GeneratedTypeInterface::class)
            ->addConst('NAME', $name)
            ->addDocBlock(self::DOCBLOCK_TEXT);

        $class->createConstructor()
            ->addArgument(Argument::create('configProcessor', ConfigProcessor::class))
            ->addArgument(Argument::create('globalVariables', GlobalVariables::class, null))
            ->append('$configLoader = ', $this->buildConfigLoader($config))
            ->append('$config = $configProcessor->process(LazyConfig::create($configLoader, $globalVariables))->load()')
            ->append('parent::__construct($config)');

        $file->addUseStatement(LazyConfig::class);

        return $file;
    }
}
