<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Builder;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Murtukov\PHPCodeGenerator\AbstractGenerator;
use Murtukov\PHPCodeGenerator\Arrays\AssocArray;
use Murtukov\PHPCodeGenerator\Arrays\NumericArray;
use Murtukov\PHPCodeGenerator\Functions\Argument;
use Murtukov\PHPCodeGenerator\Functions\ArrowFunction;
use Murtukov\PHPCodeGenerator\Functions\Closure;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;

class ObjectTypeBuilder extends AbstractBuilder
{
    public function build(array $config): GeneratorInterface
    {
        $file = new PhpFile($config['config']['name']);
        $file->setNamespace(self::DEFAULT_NAMESPACE);

        $class = $file->createClass($config['class_name'])
            ->setFinal()
            ->setExtends(ObjectType::class)
            ->addImplement(GeneratedTypeInterface::class)
            ->addConst('NAME', $config['config']['name']);

        $class->createDocBlock("THIS FILE WAS GENERATED AND SHOULD NOT BE MODIFIED MANUALLY!");

        $constructor = $class->createConstructor();
        $constructor->createArgument('configProcessor', ConfigProcessor::class);
        $constructor->createArgument('globalVariables', GlobalVariables::class, 'null');

        $caller = new Caller();

        $constructor->appendContent('configLoader', ArrowFunction::create()
            ->setExpression(AssocArray::createMultiline()
                ->addItem('name', new Literal('self::NAME'))
                ->addIfNotNull('description', $config['config']['description'])
                ->addItem('field', ArrowFunction::create()
                    ->setExpression(AssocArray::mapMultiline($config['config']['fields'],
                        function ($_, $fieldConfig) {
                            $resolveType = $caller('$globalVariable')->get('typeResolver')->resolve($fieldConfig['type']);
                            $notNullType = $caller(Type::class)::nonNull($resolveType);

                            return AssocArray::createMultiline()
                                ->addItem('type', $resolveType)
                                ->addItem('args', NumericArray::mapMultiline($fieldConfig['args'], fn($argName, $argConfig) => AssocArray::createMultiline()
                                    ->addItem('name', $argName)
                                    ->addItem('type', new Literal($this->getTypeResolveCode($argConfig['type'])))
                                    ->addIfNotEmpty('description', $argConfig['description'])
                                ))
                                ->addItem('resolve', Closure::create()
                                    ->addArgument(new Argument('value'))
                                    ->addArgument(new Argument('args'))
                                    ->addArgument(new Argument('context'))
                                    ->addArgument(new Argument('info', ResolveInfo::class))
                                    ->setReturn(NumericArray::create(['name' => 'Abraham']))
                                );
                        }
                    ))
                )
            )
        );

        $lazyConfig = $caller(LazyConfig::class)::create('$configLoader', '$globalVariables');
        $processor = $caller('$configProcessor')->process($lazyConfig))->load();

        $constructor->appendContent('config', $processor);
        $constructor->appendContent(Caller::parent()::construct('$config'));

        return $file;
    }
}
