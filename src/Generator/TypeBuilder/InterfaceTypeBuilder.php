<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use GraphQL\Type\Definition\InterfaceType;
use Murtukov\PHPCodeGenerator\Arrays\AssocArray;
use Murtukov\PHPCodeGenerator\Arrays\NumericArray;
use Murtukov\PHPCodeGenerator\Functions\ArrowFunction;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;

class InterfaceTypeBuilder implements TypeBuilderInterface
{
    public static function build(array $config, string $namespace): GeneratorInterface
    {
        $className = $config['name'].'Type';

        $file = PhpFile::create($className.'php')->setNamespace($namespace);

        $class = $file->createClass($className)
            ->setFinal()
            ->setExtends(InterfaceType::class)
            ->addImplement(GeneratedTypeInterface::class);

        $class->createProperty('NAME')
            ->setPrivate()
            ->setConst()
            ->setDefaulValue('Character');

        $constructor = $class->createConstructor();

        $configLoader = ArrowFunction::create(
            AssocArray::createMultiline()
                ->addItem('name', $config['name'])
                ->addIfNotNull('description', $config['description'] ?? null)
                ->addIfNotNull('resolveType', self::resolveTypeClosure($config['resolveType'] ?? null))
                ->addItem('fields', self::buildFieldsClosure($config['fields']))
        );

        $constructor
            ->append('$configLoader = ', $configLoader)
            ->append('$config = $configProcessor->process(LazyConfig::create($configLoader, $globalVariables))->load()')
            ->append('parent::__construct($config)')
        ;

        return $file;
    }

    private static function buildFieldsClosure(array $fields): GeneratorInterface
    {
        return ArrowFunction::create(
            AssocArray::mapMultiline($fields, fn($name, $config) =>
                AssocArray::createMultiline()
                    ->addItem('type', self::buildType($config['type']))
                    ->addIfNotNull('description', $config['description'] ?? null)
                    ->addIfNotNull('deprecationReason', $config['deprecationReason'] ?? null)
                    ->ifTrue(!empty($config['args']))
                        ->addItem('args', NumericArray::mapMultiline($config['args'], ['self', 'buildArg']))
            )
        );
    }

    private static function buildType(array $config)
    {

    }

    private static function buildArg(string $name, array $config)
    {
        return AssocArray::createMultiline()
            ->addItem('name', $name)
            ->addItem('type', self::buildType($config['type']))
            ->addIfNotNull('description', $config['description'] ?? null)
            ->addIfNotNull('defaultValue', $config['defaultValue'] ?? null)
        ;
    }

    private static function resolveTypeClosure($closure)
    {

    }
}
