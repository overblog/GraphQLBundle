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
use GraphQL\Language\AST\InlineFragment;
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

    /**
     * Set max query complexity. If equal to 0 no check is done. Must be greater or equal to 0.
     *
     * @param $maxQueryComplexity
     */
    public static function setMaxQueryComplexity($maxQueryComplexity)
    {
        self::checkIfGreaterOrEqualToZero('maxQueryComplexity', $maxQueryComplexity);

        self::$maxQueryComplexity = (int) $maxQueryComplexity;
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
                    'leave' => function (OperationDefinition $operationDefinition) use ($context, &$complexity) {
                        $complexity = $this->fieldComplexity($operationDefinition, $complexity);

                        if ($complexity > $this->getMaxQueryComplexity()) {
                            return new Error($this->maxQueryComplexityErrorMessage($this->getMaxQueryComplexity(), $complexity));
                        }
                    },
                ],
            ]
        );
    }

    private function fieldComplexity($node, $complexity = 0)
    {
        if (isset($node->selectionSet) && $node->selectionSet instanceof SelectionSet) {
            foreach ($node->selectionSet->selections as $childNode) {
                $complexity = $this->nodeComplexity($childNode, $complexity);
            }
        }

        return $complexity;
    }

    private function nodeComplexity(Node $node, $complexity = 0)
    {
        switch ($node->kind) {
            case Node::FIELD:
                /* @var Field $node */
                // default values
                $args = [];
                $complexityFn = \Overblog\GraphQLBundle\Definition\FieldDefinition::DEFAULT_COMPLEXITY_FN;

                // calculate children complexity if needed
                $childrenComplexity = 0;

                // node has children?
                if (isset($node->selectionSet)) {
                    $childrenComplexity = $this->fieldComplexity($node);
                }

                $astFieldInfo = $this->astFieldInfo($node);
                $fieldDef = $astFieldInfo[1];

                if ($fieldDef instanceof FieldDefinition) {
                    $args = $this->buildFieldArguments($node);
                    //get complexity fn using fieldDef complexity
                    if (method_exists($fieldDef, 'getComplexityFn')) {
                        $complexityFn = $fieldDef->getComplexityFn();
                    }
                }

                $complexity += call_user_func_array($complexityFn, [$childrenComplexity, $args]);
                break;

            case Node::INLINE_FRAGMENT:
                /* @var InlineFragment $node */
                // node has children?
                if (isset($node->selectionSet)) {
                    $complexity = $this->fieldComplexity($node, $complexity);
                }
                break;

            case Node::FRAGMENT_SPREAD:
                /* @var FragmentSpread $node */
                $fragment = $this->getFragment($node);

                if (null !== $fragment) {
                    $complexity = $this->fieldComplexity($fragment, $complexity);
                }
                break;
        }

        return $complexity;
    }

    private function astFieldInfo(Field $field)
    {
        $fieldName = $this->getFieldName($field);
        $astFieldInfo = [null, null];
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

        $args = [];

        if ($fieldDef instanceof FieldDefinition) {
            $variableValues = Values::getVariableValues(
                $this->context->getSchema(),
                $this->variableDefs,
                $rawVariableValues
            );
            $args = Values::getArgumentValues($fieldDef->args, $node->arguments, $variableValues);
        }

        return $args;
    }

    protected function isEnabled()
    {
        return $this->getMaxQueryComplexity() !== static::DISABLED;
    }
}
