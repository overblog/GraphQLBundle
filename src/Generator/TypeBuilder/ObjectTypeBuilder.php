<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use GraphQL\Type\Definition\ObjectType;
use Murtukov\PHPCodeGenerator\Arrays\AssocArray;
use Murtukov\PHPCodeGenerator\Arrays\NumericArray;
use Murtukov\PHPCodeGenerator\Functions\Argument;
use Murtukov\PHPCodeGenerator\Functions\ArrowFunction;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;

class ObjectTypeBuilder implements TypeBuilderInterface
{

    public static function build(array $config, string $namespace): GeneratorInterface
    {
        $className = $config['name'].'Type';

        $file = new PhpFile($className.'php');

        $class = $file->createClass($className)
            ->setFinal()
            ->setExtends(ObjectType::class)
            ->addImplement(GeneratedTypeInterface::class)
            ->addConst('NAME', "'{$config['config']['name']}'");

        $class->createDocBlock("This class was generated and should not be edited manually.");

        $class->createConstructor()
            ->addArgument(Argument::create('configProcessor', ConfigProcessor::class))
            ->addArgument(Argument::create('globalVariables', GlobalVariables::class, 'null'))
            ->append('$configLoader = ', ArrowFunction::create()
                ->setExpression(AssocArray::createMultiline()
                    ->addItem('name', 'self::NAME')
                    ->addIfNotNull('description', $config['config']['description'] ?? null)
                    ->addItem('fields', ArrowFunction::create()
                        ->setExpression(AssocArray::mapMultiline($config['config']['fields'],
                            fn($_, $fieldConfig) => (
                                AssocArray::createMultiline()
                                    ->addItem('type', self::getTypeResolveCode($fieldConfig['type']))
                                    ->ifTrue(fn() => !empty($fieldConfig['args']))
                                        ->addItem('args', NumericArray::mapMultiline($fieldConfig['args'],
                                            fn($argName, $argConfig) => (
                                                AssocArray::createMultiline()
                                                    ->addItem('name', $argName)
                                                    ->addItem('type', $this->getTypeResolveCode($argConfig['type']))
                                                    ->addIfNotEmpty('description', $argConfig['description'] ?? null)
                                            )
                                        )
                                        ->addItem('resolve', $this->buildResolve($fieldConfig['resolve']))
                                    )
                            )
                        ))
                    )
                )
            )
            ->append('$config = $configProcessor->process(LazyConfig::create($configLoader, $globalVariables))->load()')
            ->append('parent::__construct($config)')
        ;

        return $file;
    }

    private static function getTypeResolveCode($arg): string
    {
        return "Type::nonNull(\$globalVariables->get('typeResolver')->resolve('$arg'))";
    }
}
