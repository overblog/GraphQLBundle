<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\TypeNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Type;
use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\Closure;
use Murtukov\PHPCodeGenerator\Collection as MurtucovCollection;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage as EL;
use Overblog\GraphQLBundle\Generator\Collection;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Generator\Exception\GeneratorException;
use Overblog\GraphQLBundle\Generator\Model\ArgumentConfig;
use Overblog\GraphQLBundle\Generator\Model\FieldConfig;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;
use Overblog\GraphQLBundle\Generator\ResolveInstructionBuilder;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Generator\ValidationRulesBuilder;

class FieldsBuilder implements ConfigBuilderInterface
{
    protected const BUILT_IN_TYPES = [Type::STRING, Type::INT, Type::FLOAT, Type::BOOLEAN, Type::ID];

    protected ExpressionConverter $expressionConverter;
    protected ResolveInstructionBuilder $resolveInstructionBuilder;
    protected ValidationRulesBuilder $validationRulesBuilder;

    public function __construct(
        ExpressionConverter $expressionConverter,
        ResolveInstructionBuilder $resolveInstructionBuilder,
        ValidationRulesBuilder $validationRulesBuilder
    ) {
        $this->expressionConverter = $expressionConverter;
        $this->resolveInstructionBuilder = $resolveInstructionBuilder;
        $this->validationRulesBuilder = $validationRulesBuilder;
    }

    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        // only by object, input-object and interface types
        if (!empty($typeConfig->fields)) {
            $builder->addItem('fields', ArrowFunction::new(
                Collection::map(
                    $typeConfig->fields,
                    fn (array $fieldConfig, string $fieldName) => $this->buildField(new FieldConfig($fieldConfig, $fieldName), $typeConfig, $phpFile)
                )
            ));
        }
    }

    /**
     * Render example:
     * <code>
     *      [
     *          'type' => {@see buildType},
     *          'description' => 'Some description.',
     *          'deprecationReason' => 'This field will be removed soon.',
     *          'args' => fn() => [
     *              {@see buildArg},
     *              {@see buildArg},
     *               ...
     *           ],
     *          'resolve' => {@see \Overblog\GraphQLBundle\Generator\ResolveInstructionBuilder::build()},
     *          'complexity' => {@see buildComplexity},
     *      ]
     * </code>
     *
     * @return GeneratorInterface|Collection|string
     *
     * @throws GeneratorException
     *
     * @internal
     */
    protected function buildField(FieldConfig $fieldConfig, TypeConfig $typeConfig, PhpFile $phpFile): MurtucovCollection
    {
        // TODO(any): modify `InputValidator` and `TypeDecoratorListener` to support it before re-enabling this
        // see https://github.com/overblog/GraphQLBundle/issues/973
        // If there is only 'type', use shorthand
        /*if (1 === count($fieldConfig) && isset($fieldConfig->type)) {
            return $this->buildType($fieldConfig->type);
        }*/

        $field = Collection::assoc()
            ->addItem('type', $this->buildType($fieldConfig->type, $phpFile));

        // only for object types
        if (isset($fieldConfig->resolve)) {
            if (isset($fieldConfig->validation)) {
                $field->addItem('validation', $this->validationRulesBuilder->build($fieldConfig->validation, $phpFile));
            }
            $field->addItem('resolve', $this->resolveInstructionBuilder->build($typeConfig, $fieldConfig->resolve, $fieldConfig->getName(), $fieldConfig->validationGroups ?? null));
        }

        if (isset($fieldConfig->deprecationReason)) {
            $field->addItem('deprecationReason', $fieldConfig->deprecationReason);
        }

        if (isset($fieldConfig->description)) {
            $field->addItem('description', $fieldConfig->description);
        }

        if (!empty($fieldConfig->args)) {
            $field->addItem('args', Collection::map(
                $fieldConfig->args,
                fn (array $argConfig, string $argName) => $this->buildArg(new ArgumentConfig($argConfig, $argName), $phpFile),
                false
            ));
        }

        if (isset($fieldConfig->complexity)) {
            $field->addItem('complexity', $this->buildComplexity($fieldConfig->complexity));
        }

        if (isset($fieldConfig->public)) {
            $field->addItem('public', $this->buildPublic($fieldConfig->public));
        }

        if (isset($fieldConfig->access)) {
            $field->addItem('access', $this->buildAccess($fieldConfig->access));
        }

        if (!empty($fieldConfig->access) && is_string($fieldConfig->access) && EL::expressionContainsVar('object', $fieldConfig->access)) {
            $field->addItem('useStrictAccess', false);
        }

        if ($typeConfig->isInputObject()) {
            if (array_key_exists('defaultValue', $fieldConfig)) {
                $field->addItem('defaultValue', $fieldConfig->defaultValue);
            }

            if (isset($fieldConfig->validation)) {
                $field->addItem('validation', $this->validationRulesBuilder->build($fieldConfig->validation, $phpFile));
            }
        }

        return $field;
    }

    /**
     * Builds an arrow function from a string with an expression prefix,
     * otherwise just returns the provided value back untouched.
     *
     * Render example (if expression):
     * <code>
     *      fn($value, $args, $context, $info, $object) => $services->get('private_service')->hasAccess()
     * </code>
     *
     * @param string|mixed $access
     *
     * @return ArrowFunction|mixed
     */
    protected function buildAccess($access)
    {
        if (EL::isStringWithTrigger($access)) {
            $expression = $this->expressionConverter->convert($access);

            return ArrowFunction::new()
                ->addArguments('value', 'args', 'context', 'info', 'object')
                ->setExpression(Literal::new($expression));
        }

        return $access;
    }

    /**
     * Render example:
     * <code>
     *  [
     *      'name' => 'username',
     *      'type' => {@see buildType},
     *      'description' => 'Some fancy description.',
     *      'defaultValue' => 'admin',
     *  ]
     * </code>
     *
     * @throws GeneratorException
     *
     * @internal
     */
    protected function buildArg(ArgumentConfig $argConfig, PhpFile $phpFile): Collection
    {
        // Convert to object for better readability

        $arg = Collection::assoc()
            ->addItem('name', $argConfig->getName())
            ->addItem('type', $this->buildType($argConfig->type, $phpFile));

        if (isset($argConfig->description)) {
            $arg->addIfNotEmpty('description', $argConfig->description);
        }

        if (array_key_exists('defaultValue', $argConfig)) {
            $arg->addItem('defaultValue', $argConfig->defaultValue);
        }

        if (isset($argConfig->validation) && !empty($argConfig->validation)) {
            if (isset($argConfig->validation['cascade']) && \in_array($argConfig->type, self::BUILT_IN_TYPES, true)) {
                throw new GeneratorException('Cascade validation cannot be applied to built-in types.');
            }

            $arg->addIfNotEmpty('validation', $this->validationRulesBuilder->build($argConfig->validation, $phpFile));
        }

        return $arg;
    }

    /**
     * Builds a closure or an arrow function, depending on whether the `args` param is provided.
     *
     * Render example (closure):
     * <code>
     *      function ($value, $arguments) use ($services) {
     *          $args = $services->get('argumentFactory')->create($arguments);
     *          return ($args['age'] + 5);
     *      }
     * </code>
     *
     * Render example (arrow function):
     * <code>
     *      fn($childrenComplexity) => ($childrenComplexity + 20);
     * </code>
     *
     * @param string|mixed $complexity
     */
    protected function buildComplexity($complexity): GeneratorInterface
    {
        if (EL::isStringWithTrigger($complexity)) {
            $expression = $this->expressionConverter->convert($complexity);

            if (EL::expressionContainsVar('args', $complexity)) {
                $gqlServices = TypeGenerator::GRAPHQL_SERVICES_EXPR;

                return Closure::new()
                    ->addArgument('childrenComplexity')
                    ->addArgument('arguments', '', [])
                    ->bindVar(TypeGenerator::GRAPHQL_SERVICES)
                    ->append('$args = ', "{$gqlServices}->get('argumentFactory')->create(\$arguments)")
                    ->append('return ', $expression);
            }

            $arrow = ArrowFunction::new(is_string($expression) ? new Literal($expression) : $expression);

            if (EL::expressionContainsVar('childrenComplexity', $complexity)) {
                $arrow->addArgument('childrenComplexity');
            }

            return $arrow;
        }

        return new ArrowFunction(0);
    }

    /**
     * Builds an arrow function from a string with an expression prefix,
     * otherwise just returns the provided value back untouched.
     *
     * Render example (if expression):
     *
     *      fn($fieldName, $typeName = self::NAME) => ($fieldName == "name")
     *
     * @param string|mixed $public
     *
     * @return ArrowFunction|mixed
     */
    protected function buildPublic($public)
    {
        if (EL::isStringWithTrigger($public)) {
            $expression = $this->expressionConverter->convert($public);
            $arrow = ArrowFunction::new(Literal::new($expression));

            if (EL::expressionContainsVar('fieldName', $public)) {
                $arrow->addArgument('fieldName');
            }

            if (EL::expressionContainsVar('typeName', $public)) {
                $arrow->addArgument('fieldName');
                $arrow->addArgument('typeName', '', new Literal('self::NAME'));
            }

            return $arrow;
        }

        return $public;
    }

    /**
     * Converts a native GraphQL type string into the `webonyx/graphql-php`
     * type literal. References to user-defined types are converted into
     * TypeResovler method call and wrapped into a closure.
     *
     * Render examples:
     *
     *  -   "String"   -> Type::string()
     *  -   "String!"  -> Type::nonNull(Type::string())
     *  -   "[String!] -> Type::listOf(Type::nonNull(Type::string()))
     *  -   "[Post]"   -> Type::listOf($services->getType('Post'))
     *
     * @return GeneratorInterface|string
     */
    protected function buildType(string $typeDefinition, PhpFile $phpFile)
    {
        $typeNode = Parser::parseType($typeDefinition);

        $isReference = false;
        $type = $this->wrapTypeRecursive($typeNode, $isReference, $phpFile);

        if ($isReference) {
            // References to other types should be wrapped in a closure
            // for performance reasons
            return ArrowFunction::new($type);
        }

        return $type;
    }

    /**
     * Used by {@see buildType}.
     *
     * @param TypeNode|mixed $typeNode
     *
     * @return Literal|string
     */
    protected function wrapTypeRecursive($typeNode, bool &$isReference, PhpFile $phpFile)
    {
        switch ($typeNode->kind) {
            case NodeKind::NON_NULL_TYPE:
                $innerType = $this->wrapTypeRecursive($typeNode->type, $isReference, $phpFile);
                $type = Literal::new("Type::nonNull($innerType)");
                $phpFile->addUse(Type::class);
                break;
            case NodeKind::LIST_TYPE:
                $innerType = $this->wrapTypeRecursive($typeNode->type, $isReference, $phpFile);
                $type = Literal::new("Type::listOf($innerType)");
                $phpFile->addUse(Type::class);
                break;
            default: // NodeKind::NAMED_TYPE
                if (in_array($typeNode->name->value, static::BUILT_IN_TYPES)) {
                    $name = strtolower($typeNode->name->value);
                    $type = Literal::new("Type::$name()");
                    $phpFile->addUse(Type::class);
                } else {
                    $name = $typeNode->name->value;
                    $gqlServices = TypeGenerator::GRAPHQL_SERVICES_EXPR;
                    $type = "{$gqlServices}->getType('$name')";
                    $isReference = true;
                }
                break;
        }

        return $type;
    }
}
