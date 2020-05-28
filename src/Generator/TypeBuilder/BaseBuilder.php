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
use Murtukov\PHPCodeGenerator\Functions\ArrowFunction;
use Murtukov\PHPCodeGenerator\Functions\Closure;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Literal;
use Overblog\GraphQLBundle\Generator\AssocArray;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use RuntimeException;
use Symfony\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\Token;
use function array_map;
use function count;
use function extract;
use function strpos;

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
     * @throws RuntimeException
     */
    protected static function buildType($typeDefinition)
    {
        $typeNode = Parser::parseType($typeDefinition);
        return self::wrapTypeRecursive($typeNode);
    }

    /**
     * @param $typeNode
     * @return DependencyAwareGenerator|string
     * @throws RuntimeException
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
            default: throw new RuntimeException('Unrecognized node kind.');
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

        $configLoader = AssocArray::multiline()
            ->addItem('name', new Literal('self::NAME'))
            ->addItem('fields', ArrowFunction::new(
                AssocArray::map($fields, [$this, 'buildField'])
            ));

        if (isset($description)) {
            $configLoader->addItem('description', $description);
        }

        if (!empty($interfaces)) {
            $items = array_map(fn($type) => "\$globalVariable->get('typeResolver')->resolve('$type')", $interfaces);

            if (count($interfaces) > 1) {
                $array = NumericArray::multiline($items);
            } else {
                $array = NumericArray::new($items);
            }

            $configLoader->addItem('interfaces', ArrowFunction::new($array));
        }

        if (isset($resolveType)) {
            $configLoader->addItem('resolveType', $this->buildResolveType($resolveType));
        }

        return new ArrowFunction($configLoader);
    }

    /**
     * @param mixed $resolve
     * @return Closure
     */
    public function buildResolve($resolve)
    {
        $contains = $this->expressionContainsVar('validator', substr($resolve, 2));

        if ($this->expressionConverter->check($resolve)) {
            $expression = $this->expressionConverter->convert($resolve);
            return Closure::new()
                ->addArgument('value')
                ->addArgument('args')
                ->addArgument('context')
                ->addArgument('info', ResolveInfo::class)
                ->append('return = ', $expression);
        }

        return $resolve;
    }

    /**
     * Checks if an expression string containst specific variable
     *
     * @param string $name       - Name of the searched variable
     * @param string $expression - Expression string to search in
     */
    public function expressionContainsVar(string $name, string $expression): bool
    {
        $stream = (new Lexer())->tokenize($expression);
        $current = &$stream->current;

        while (!$stream->isEOF()) {
            if ($name === $current->value && Token::NAME_TYPE === $current->type) {
                // Also check that it's not a functions name
                $stream->next();
                if ("(" !== $current->value) {
                    $contained = true;
                    break;
                }
                continue;
            }

            $stream->next();
        }

        return $contained ?? false;
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

        $field = AssocArray::multiline()
            ->addItem('type', self::buildType($type));

        if (isset($resolve)) {
            $field->addItem('resolve', $this->buildResolve($resolve));
        }

        if (isset($deprecationReason)) {
            $field->addItem('deprecationReason', $deprecationReason);
        }

        if (isset($description)) {
            $field->addItem('description', $description);
        }

        if (!empty($args)) {
            $field->addItem('args', NumericArray::map($args, [$this, 'buildArg']));
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

        $arg = AssocArray::multiline()
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
                return Closure::new()
                    ->addArgument('childrenComplexity')
                    ->addArgument('arguments', '', [])
                    ->append('$args = $globalVariable->get(\'argumentFactory\')->create($arguments)')
                    ->append('return ', $expression)
                ;
            }

            return ArrowFunction::new($expression)->addArgument('childrenComplexity');
        }

        return $complexity;
    }

    public function buildPublic($public)
    {
        if ($this->expressionConverter->check($public)) {
            $expression = $this->expressionConverter->convert($public);

            return ArrowFunction::new($expression)
                ->addArgument('fieldName')
                ->addArgument('fieldName', '', new Literal('self::NAME'))
            ;
        }

        return $public;
    }

    public function buildAccess($access)
    {
        if ($this->expressionConverter->check($access)) {
            $expression = $this->expressionConverter->convert($access);

            return ArrowFunction::new()
                ->addArgument('value')
                ->addArgument('args')
                ->addArgument('context')
                ->addArgument('info', ResolveInfo::class)
                ->addArgument('object')
                ->setExpression($expression);
        }

        return $access;
    }

    public function buildResolveType($resolveType)
    {
        if ($this->expressionConverter->check($resolveType)) {
            $expression = $this->expressionConverter->convert($resolveType);

            return ArrowFunction::new()
                ->addArgument('value')
                ->addArgument('context')
                ->addArgument('info', ResolveInfo::class)
                ->setExpression($expression);
        }

        return $resolveType;
    }
}
