<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Request\Validator\Rule;

use GraphQL\Error;
use GraphQL\Executor\Values;
use GraphQL\Language\AST\Field;
use GraphQL\Language\AST\FragmentSpread;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\OperationDefinition;
use GraphQL\Language\AST\SelectionSet;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Validator\ValidationContext;

class QueryComplexity extends AbstractQuerySecurity
{
    const DEFAULT_QUERY_MAX_COMPLEXITY = self::DISABLED;

    private static $maxQueryComplexity;

    private static $rawVariableValues = [];

    /** @var callable[] */
    private static $complexityCalculators = [];

    private $variableDefs;

    private $fieldAstAndDefs;

    /**
     * @var ValidationContext
     */
    private $context;

    public function __construct($maxQueryDepth = self::DEFAULT_QUERY_MAX_COMPLEXITY)
    {
        $this->setMaxQueryComplexity($maxQueryDepth);
    }

    public static function maxQueryComplexityErrorMessage($max, $count)
    {
        return sprintf('Max query complexity should be %d but got %d.', $max, $count);
    }

    public static function addComplexityCalculator($name, callable $complexityCalculator)
    {
        self::$complexityCalculators[$name] = $complexityCalculator;
    }

    public static function hasComplexityCalculator($name)
    {
        return isset(self::$complexityCalculators[$name]);
    }

    public static function getComplexityCalculator($name)
    {
        return static::hasComplexityCalculator($name) ? self::$complexityCalculators[$name] : null;
    }

    public static function removeComplexityCalculator($name)
    {
        unset(self::$complexityCalculators[$name]);
    }

    /**
     * Set max query complexity. If equal to 0 no check is done. Must be greater or equal to 0.
     *
     * @param $maxQueryComplexity
     */
    public static function setMaxQueryComplexity($maxQueryComplexity)
    {
        self::checkIfGreaterOrEqualToZero('maxQueryComplexity', $maxQueryComplexity);

        self::$maxQueryComplexity = (int)$maxQueryComplexity;
    }

    public static function getMaxQueryComplexity()
    {
        return self::$maxQueryComplexity;
    }

    public static function setRawVariableValues(array $rawVariableValues = null)
    {
        self::$rawVariableValues = $rawVariableValues ?: [];
    }

    public static function getRawVariableValues()
    {
        return self::$rawVariableValues;
    }

    public function __invoke(ValidationContext $context)
    {
        $this->context = $context;

        $this->variableDefs = new \ArrayObject();
        $this->fieldAstAndDefs = new \ArrayObject();
        $complexity = 0;

        return $this->invokeIfNeeded(
            $context,
            [
                // Visit FragmentDefinition after visiting FragmentSpread
                'visitSpreadFragments' => true,
                Node::SELECTION_SET => function (SelectionSet $selectionSet) use ($context) {
                    $this->fieldAstAndDefs = $this->collectFieldASTsAndDefs(
                        $context,
                        $context->getParentType(),
                        $selectionSet,
                        null,
                        $this->fieldAstAndDefs
                    );
                },
                Node::VARIABLE_DEFINITION => function ($def) {
                    $this->variableDefs[] = $def;

                    return Visitor::skipNode();
                },
                Node::OPERATION_DEFINITION => [
                    'leave' => function (OperationDefinition $operationDefinition) use ($context, $complexity) {
                        // check complexity only on first rootTypes children and ignore check on introspection query
                        //if (!$this->isIntrospectionType($context)) {
                            $type = $context->getType();
                            $complexity = $this->fieldComplexity($operationDefinition, $type->name, $complexity);

                            if ($complexity > $this->getMaxQueryComplexity()) {
                                return new Error($this->maxQueryComplexityErrorMessage($this->getMaxQueryComplexity(), $complexity));
                            }
                        //}
                    },
                ],
            ]
        );
    }

    private function fieldComplexity(Node $node, $typeName, $complexity = 0)
    {
        if (!isset($node->selectionSet)) {
            return $complexity;
        }

        foreach ($node->selectionSet->selections as $childNode) {
            $complexity = $this->nodeComplexity($childNode, $typeName, $complexity);
        }

        return $complexity;
    }

    private function nodeComplexity(Node $node, $typeName, $complexity = 0)
    {
        switch ($node->kind) {
            case Node::FIELD:
                // calculate children complexity if needed
                $childrenComplexity = 0;

                $astFieldInfo = $this->astFieldInfo($node);
                /** @var ValidationContext|null $fieldValidationContext */
                $fieldValidationContext = $astFieldInfo[2];
                /** @var FieldDefinition|null $fieldDef */
                $fieldDef = $astFieldInfo[1];

                // node has children?
                if (isset($node->selectionSet)) {
                    $type = $fieldDef->getType();
                    $childrenComplexity = $this->fieldComplexity($node, $type->name);
                }

                // default complexity calculator
                $complexity = $complexity + $childrenComplexity + 1;

                $complexityCalculatorName = $typeName . '.' . $this->getFieldName($node);
                // custom complexity is set ?
                if (null !== $complexityCalculator = static::getComplexityCalculator($complexityCalculatorName)) {
                    $args = $this->buildFieldArguments($node);
                    //get field complexity using custom complexityCalculator
                    $complexity = call_user_func_array($complexityCalculator, [$fieldValidationContext, $args, $childrenComplexity]);
                }
                break;

            case Node::INLINE_FRAGMENT:
                // node has children?
                if (isset($node->selectionSet)) {
                    $complexity = $this->fieldComplexity($node, $typeName, $complexity);
                }
                break;

            case Node::FRAGMENT_SPREAD:
                $fragment = $this->getFragment($node);

                if (null !== $fragment) {
                    $complexity = $this->fieldComplexity($fragment, $typeName, $complexity);
                }
                break;
        }

        return $complexity;
    }

    private function astFieldInfo(Field $field)
    {
        $fieldName = $this->getFieldName($field);
        $astFieldInfo = [null, null, null];
        if (isset($this->fieldAstAndDefs[$fieldName])) {
            foreach ($this->fieldAstAndDefs[$fieldName] as $astAndDef) {
                if ($astAndDef[0] == $field) {
                    $astFieldInfo = $astAndDef;
                    break;
                }
            }
        }

        return $astFieldInfo;
    }

    private function buildFieldArguments(Field $node)
    {
        $rawVariableValues = $this->getRawVariableValues();
        $astFieldInfo = $this->astFieldInfo($node);
        $fieldDef = $astFieldInfo[1];

        if (!$fieldDef instanceof FieldDefinition) {
            return;
        }

        $variableValues = Values::getVariableValues(
            $this->context->getSchema(),
            $this->variableDefs,
            $rawVariableValues
        );
        $args = Values::getArgumentValues($fieldDef->args, $node->arguments, $variableValues);

        return $args;
    }

    protected function isEnabled()
    {
        return $this->getMaxQueryComplexity() !== static::DISABLED;
    }
}
