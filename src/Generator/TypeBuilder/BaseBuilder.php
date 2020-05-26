<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\TypeBuilder;

use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Murtukov\PHPCodeGenerator\Arrays\NumericArray;
use Murtukov\PHPCodeGenerator\Call;
use Murtukov\PHPCodeGenerator\Config;
use Murtukov\PHPCodeGenerator\ConverterInterface;
use Murtukov\PHPCodeGenerator\DependencyAwareGenerator;
use Murtukov\PHPCodeGenerator\Functions\Argument;
use Murtukov\PHPCodeGenerator\Functions\ArrowFunction;
use Murtukov\PHPCodeGenerator\Functions\Closure;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Literal;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Generator\AssocArray;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;

abstract class BaseBuilder implements TypeBuilderInterface
{
    protected const DOCBLOCK_TEXT = "This file was generated and should not be edited manually.";
    protected const BUILT_IN_TYPES = [Type::STRING, Type::INT, Type::FLOAT, Type::BOOLEAN, Type::ID];

    protected ExpressionConverter $expressionConverter;
    protected string $namespace;

    public function __construct(ExpressionConverter $expressionConverter, string $namespace)
    {
        $this->expressionConverter = $expressionConverter;
        $this->namespace = $namespace;

        // Register additional stringifier for the php code generator
        Config::registerConverter($expressionConverter, ConverterInterface::TYPE_STRING);
    }

    /**
     * @param $typeDefinition
     * @return GeneratorInterface|string
     * @throws \RuntimeException
     */
    protected static function buildType($typeDefinition)
    {
        $typeNode = Parser::parseType($typeDefinition);
        return self::wrapTypeRecursive($typeNode);
    }

    /**
     * @param $typeNode
     * @return DependencyAwareGenerator|string
     * @throws \RuntimeException
     */
    private static function wrapTypeRecursive($typeNode)
    {
        $call = new Call();

        switch ($typeNode->kind) {
            case NodeKind::NON_NULL_TYPE:
                $innerType = self::wrapTypeRecursive($typeNode->type);
                $type = $call(Type::class)::notNull($innerType);
                break;
            case NodeKind::LIST_TYPE:
                $innerType = self::wrapTypeRecursive($typeNode->type);
                $type = $call(Type::class)::listOf($innerType);
                break;
            case NodeKind::NAMED_TYPE:
                if (in_array($typeNode->name->value, self::BUILT_IN_TYPES)) {
                    $name = lcfirst($typeNode->name->value);
                    $type = $call(Type::class)::$name();
                } else {
                    $name = $typeNode->name->value;
                    $type = "\$globalVariables->get('typeResolver')->resolve('$name')";
                }
                break;
            default: throw new \RuntimeException('Unrecognized node kind.');
        }

        return $type;
    }

    public function buildConfigLoader($config)
    {
        /**
         * @var array       $fields
         * @var string|null $description
         * @var array|null  $interfaces
         * @var string|null $resolveType
         */
        extract($config);

        $configLoader = AssocArray::createMultiline()
            ->addItem('name', new Literal('self::NAME'))
            ->addItem('fields', ArrowFunction::create(
                AssocArray::mapMultiline($fields, [$this, 'buildField'])
            ));

        if (isset($description)) {
            $configLoader->addItem('description', $description);
        }

        if (!empty($interfaces)) {
            $items = array_map(fn($type) => "\$globalVariable->get('typeResolver')->resolve('$type')", $interfaces);

            if (count($interfaces) > 1) {
                $array = NumericArray::createMultiline($items);
            } else {
                $array = NumericArray::create($items);
            }

            $configLoader->addItem('interfaces', ArrowFunction::create($array));
        }

        if (isset($resolveType)) {
            $configLoader->addItem('resolveType', $this->buildResolveType($resolveType));
        }

        return new ArrowFunction($configLoader);
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

        if (isset($deprecationReason)) {
            $field->addItem('deprecationReason', $deprecationReason);
        }

        if (isset($description)) {
            $field->addItem('description', $description);
        }

        if (!empty($args)) {
            $field->addItem('args', NumericArray::mapMultiline($args, [$this, 'buildArg']));
        }

        if (isset($complexity)) {
            $field->addItem('complexity', $this->buildComplexity($complexity));
        }

        if (isset($public)) {
            $field->addItem('public', $this->buildPublic($public));
        }

        if (isset($access)) {
            $field->addItem('access', $this->buildAccess($access));
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

    /**
     * @param string|int $complexity
     * @return Closure|mixed
     */
    public function buildComplexity($complexity)
    {
        if ($this->expressionConverter->check($complexity)) {
            $expression = $this->expressionConverter->convert($complexity);

            // todo: add use() to closure
            if (strpos($complexity, 'args') !== false) {
                return Closure::create()
                    ->addArgument(new Argument('childrenComplexity'))
                    ->addArgument(new Argument('arguments', '', []))
                    ->append('$args = $globalVariable->get(\'argumentFactory\')->create($arguments)')
                    ->append('return ', $expression)
                ;
            }

            return ArrowFunction::create($expression)->addArgument(new Argument('childrenComplexity'));
        }

        return $complexity;
    }

    public function buildPublic($public)
    {
        if ($this->expressionConverter->check($public)) {
            $expression = $this->expressionConverter->convert($public);

            return ArrowFunction::create($expression)
                ->addArgument(new Argument('fieldName'))
                ->addArgument(new Argument('fieldName', '', new Literal('self::NAME')))
            ;
        }

        return $public;
    }

    public function buildAccess($access)
    {
        if ($this->expressionConverter->check($access)) {
            $expression = $this->expressionConverter->convert($access);

            return ArrowFunction::create()
                ->addArgument(new Argument('value'))
                ->addArgument(new Argument('args'))
                ->addArgument(new Argument('context'))
                ->addArgument(new Argument('info', ResolveInfo::class))
                ->addArgument(new Argument('object'))
                ->setExpression($expression);
        }

        return $access;
    }

    public function buildResolveType($resolveType)
    {
        if ($this->expressionConverter->check($resolveType)) {
            $expression = $this->expressionConverter->convert($resolveType);

            return ArrowFunction::create()
                ->addArgument(new Argument('value'))
                ->addArgument(new Argument('context'))
                ->addArgument(new Argument('info', ResolveInfo::class))
                ->setExpression($expression);
        }

        return $resolveType;
    }

    public function buildResolver($resolver)
    {

    }
}
