<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use GraphQL\Type\Definition\InterfaceType;
use Overblog\GraphQLBundle\Generator\AssocArray;
use Murtukov\PHPCodeGenerator\Arrays\NumericArray;
use Murtukov\PHPCodeGenerator\Functions\ArrowFunction;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;

class InterfaceTypeBuilder extends BaseBuilder
{
    public function build(array $config): GeneratorInterface
    {
        /**
         * @var string      $name
         * @var string|null $resolveType
         * @var array       $fields
         * @var string|null $description
         */
        extract($config);

        $file = PhpFile::create($name.'Type.php')->setNamespace($this->namespace);

        $class = $file->createClass($name.'Type')
            ->setFinal()
            ->setExtends(InterfaceType::class)
            ->addImplement(GeneratedTypeInterface::class)
            ->addConst('NAME', $name);

        $constructor = $class->createConstructor();
        $constructor->createArgument('configProcessor', ConfigProcessor::class);
        $constructor->createArgument('globalVariables', GlobalVariables::class);

        $configLoader = ArrowFunction::create(
            AssocArray::createMultiline()
                ->addItem('name', $name)
                ->addIfNotNull('description', $description ?? null)
                ->addIfNotNull('resolveType', self::resolveTypeClosure($resolveType ?? null))
                ->addItem('fields', self::buildFieldsClosure($fields))
        );

        $constructor
            ->append('$configLoader = ', $configLoader)
            ->append('$config = $configProcessor->process(LazyConfig::create($configLoader, $globalVariables))->load()')
            ->append('parent::__construct($config)')
        ;

        $file->addUseStatement(LazyConfig::class);

        return $file;
    }

    private static function buildFieldsClosure(array $fields): GeneratorInterface
    {
        return ArrowFunction::create(
            AssocArray::mapMultiline($fields, function($config /*, $name */) {
                $assocArray = AssocArray::createMultiline()
                    ->addItem('type', self::buildType($config['type']))
                    ->addIfNotNull('description', $config['description'] ?? null)
                    ->addIfNotNull('deprecationReason', $config['deprecationReason'] ?? null)
                ;

                if (!empty($config['args'])) {
                    $assocArray->addItem('args', self::buildArg($config['args']));
                }

                return $assocArray;
            })
        );
    }

    private static function buildArg(array $config)
    {
        $callback = function(string $name, array $config) {
            return AssocArray::createMultiline()
                ->addItem('name', $name)
                ->addItem('type', self::buildType($config['type']))
                ->addIfNotNull('description', $config['description'] ?? null)
                ->addIfNotNull('defaultValue', $config['defaultValue'] ?? null)
            ;
        };

        return NumericArray::mapMultiline($config['args'], $callback);
    }

    private static function resolveTypeClosure($closure)
    {

    }
}
