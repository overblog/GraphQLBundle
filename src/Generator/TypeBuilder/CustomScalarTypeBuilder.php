<?php

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use GraphQL\Type\Definition\CustomScalarType;
use Murtukov\PHPCodeGenerator\Arrays\AssocArray;
use Murtukov\PHPCodeGenerator\Functions\ArrowFunction;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;

class CustomScalarTypeBuilder implements TypeBuilderInterface
{

    public static function build(array $config, string $namespace): GeneratorInterface
    {
        $className = $config['name'].'Type';

        $file = PhpFile::create($className.'php')->setNamespace($namespace);

        $class = $file->createClass($className)
            ->setExtends(CustomScalarType::class)
            ->addImplement(GeneratedTypeInterface::class)
            ->setFinal();

        $class->createProperty('NAME')
            ->setConst()
            ->setDefaulValue($config['name']);

        $constructor = $class->createConstructor();

        $configArray = AssocArray::createMultiline()
            ->addItem('name', $config['name'])
            ->addIfNotNull('descriprion', $config['description'] ?? null)
            ->addIfNotNull('scalarType', $config['scalarType'] ?? null)
        ;

        foreach (['serialize', 'parseValue', 'parseLiteral'] as $value) {
            $closure = new ArrowFunction();

            if (is_array($config[$value])) {
                $closure->setExpression("{$config[$value][0]}::{$config[$value][1]}(...\\func_get_args())");
            } else {
                $closure->setExpression($config[$value] . '(...\\func_get_args())');
            }

            $configArray->addItem($value, $closure);
        }

        $constructor->append('$configLoader = ', ArrowFunction::create($configArray));
        $constructor->append('$config = $configProcessor->process(LazyConfig::create($configLoader, $globalVariables))->load()');
        $constructor->append('parent::__construct($config)');

        $file->addUseStatement(LazyConfig::class);

        return $file;
    }
}
