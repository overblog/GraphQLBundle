<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use GraphQL\Type\Definition\ObjectType;
use Murtukov\PHPCodeGenerator\Functions\Closure;
use Murtukov\PHPCodeGenerator\Literal;
use Overblog\GraphQLBundle\Definition\LazyConfig;
use Overblog\GraphQLBundle\Generator\AssocArray;
use Murtukov\PHPCodeGenerator\Arrays\NumericArray;
use Murtukov\PHPCodeGenerator\Functions\Argument;
use Murtukov\PHPCodeGenerator\Functions\ArrowFunction;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Definition\ConfigProcessor;
use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;

class ObjectTypeBuilder extends BaseBuilder
{
    public function build(array $config): GeneratorInterface
    {
        /**
         * @var string      $name
         * @var string|null $description
         * @var array       $fields
         */
        extract($config);

        $className = $name.'Type';

        $file = PhpFile::create($className.'php')->setNamespace($this->namespace);

        $class = $file->createClass($className)
            ->setFinal()
            ->setExtends(ObjectType::class)
            ->addImplement(GeneratedTypeInterface::class)
            ->addConst('NAME', $name)
            ->addDocBlock(self::DOCBLOCK_TEXT);

        $class->createConstructor()
            ->addArgument(Argument::create('configProcessor', ConfigProcessor::class))
            ->addArgument(Argument::create('globalVariables', GlobalVariables::class, null))
            ->append('$configLoader = ', $this->buildConfigLoader($config))
            ->append('$config = $configProcessor->process(LazyConfig::create($configLoader, $globalVariables))->load()')
            ->append('parent::__construct($config)')
        ;

        $file->addUseStatement(LazyConfig::class);

        return $file;
    }

    public function buildConfigLoader($config)
    {
        /**
         * @var string|null $description
         * @var array       $fields
         */
        extract($config);

        $expression = AssocArray::createMultiline()
            ->addItem('name', new Literal('self::NAME'));

        if (isset($description)) {
            $expression->addItem('description', $description);
        }

        if (!empty($fields)) {
            $expression->addItem('fields', ArrowFunction::create()
                ->setExpression(AssocArray::mapMultiline($fields, [$this, 'buildField']))
            );
        }

        return new ArrowFunction($expression);
    }

    public static function buildResolve($config)
    {
        $closure = Closure::create();

        return $closure;
    }

    public function buildField($fieldConfig /*, $fieldname */): AssocArray
    {
        /**
         * @var string      $type
         * @var string|null $resolve
         * @var string|null $description
         * @var array|null  $args
         * @var string|null $complexity
         * @var string|null $deprecationReason
         */
        extract($fieldConfig);

        $field = AssocArray::createMultiline()
            ->addItem('type', self::buildType($type));

        if (isset($resolve)) {
            $field->addItem('resolve', self::buildResolve($resolve));
        }

        if (isset($description)) {
            $field->addItem('deprecationReason', $deprecationReason);
        }

        if (isset($description)) {
            $field->addItem('description', $description);
        }

        if (!empty($args)) {
            $field->addItem('args', NumericArray::mapMultiline($args, [$this, 'buildArg']));
        }

        if (isset($complexity)) {
            $field->addItem('complexity', $complexity);
        }

        return $field;
    }

    public function buildArg($argConfig, $argName)
    {
        /**
         * @var string      $type
         * @var string|null $description
         * @var string|null $defaultValue
         */
        extract($argConfig);

        $arg = AssocArray::createMultiline()
            ->addItem('name', $argName)
            ->addItem('type', self::buildType($type));

        if (isset($description)) {
            $arg->addIfNotEmpty('description', $description);
        }

        if (isset($defaultValue)) {
            $arg->addIfNotEmpty('defaultValue', $defaultValue);
        }

        return $arg;
    }
}
