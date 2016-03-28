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
use GraphQL\Language\AST\SelectionSet;
use GraphQL\Language\AST\Type;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Utils\TypeInfo;
use GraphQL\Validator\ValidationContext;

class MaxQueryComplexity extends AbstractQuerySecurity
{
    const DEFAULT_QUERY_MAX_COMPLEXITY = 1000;

    private static $maxQueryComplexity;

    private static $rawVariableValues = [];

    private static $complexityMap = [];

    private $variableDefs;

    private $fieldAstAndDefs;

    /**
     * @var ValidationContext
     */
    private $context;

    public function __construct($maxQueryDepth = self::DEFAULT_QUERY_MAX_COMPLEXITY)
    {
        $this->setMaxQueryComplexity($maxQueryDepth);
        //todo delete after test
//        self::$complexityMap['User'] = function (ValidationContext $context, $args, $childrenComplexity) {
//            return 25 + $args['id'] * $childrenComplexity;
//        };
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
                Node::FIELD => [
                    'leave' => function (Field $node) use ($context, $complexity) {
                        // check complexity only on first rootTypes children and ignore check on introspection query
                        if ($this->isParentRootType($context) && !$this->isIntrospectionType($context)) {
                            $complexity = $this->nodeComplexity($node, $complexity);

                            if ($complexity > $this->getMaxQueryComplexity()) {
                                return new Error($this->maxQueryComplexityErrorMessage($this->getMaxQueryComplexity(), $complexity), [$node]);
                            }
                        }
                    },
                ],
            ]
        );
    }

    private function fieldComplexity(Node $node, $complexity = 0)
    {
        if (!isset($node->selectionSet)) {
            return $complexity;
        }

        foreach ($node->selectionSet->selections as $childNode) {
            $complexity = $this->nodeComplexity($childNode, $complexity);
        }

        return $complexity;
    }

    private function nodeComplexity(Node $node, $complexity = 0)
    {
        switch ($node->kind) {
            case Node::FIELD:
                // calculate children complexity if needed
                $childrenComplexity = 0;

                // node has children?
                if (isset($node->selectionSet)) {
                    $childrenComplexity = $this->fieldComplexity($node);
                }

                $fieldDef = $this->astFieldToFieldDef($node);

                $complexityCalculator = null;

                // custom complexity is set ?
                if ($fieldDef && isset(self::$complexityMap[$fieldDef->getType()->name])) {
                    $args = $this->buildFieldArguments($node);
                    $typeName = $fieldDef->getType()->name;

                    $complexity = call_user_func_array(self::$complexityMap[$typeName], [$this->context, $args, $childrenComplexity]);
                } else {
                    // default complexity calculator
                    $complexity = $complexity + $childrenComplexity + 1;
                }
                break;

            case Node::INLINE_FRAGMENT:
                // node has children?
                if (isset($node->selectionSet)) {
                    $complexity = $this->fieldComplexity($node, $complexity);
                }
                break;

            case Node::FRAGMENT_SPREAD:
                $fragment = $this->getFragment($node);

                if (null !== $fragment) {
                    $complexity = $this->fieldComplexity($fragment, $complexity);
                }
                break;
        }

        return $complexity;
    }

    private function getFieldName(Field $node)
    {
        $fieldName = $node->name->value;
        $responseName = $node->alias ? $node->alias->value : $fieldName;

        return $responseName;
    }

    private function astFieldToFieldDef(Field $field)
    {
        $fieldName = $this->getFieldName($field);
        $fieldDef = null;
        if (isset($this->fieldAstAndDefs[$fieldName])) {
            foreach ($this->fieldAstAndDefs[$fieldName] as $astAndDef) {
                if ($astAndDef[0] == $field) {
                    /** @var FieldDefinition $fieldDef */
                    $fieldDef = $astAndDef[1];
                    break;
                }
            }
        }

        return $fieldDef;
    }

    private function buildFieldArguments(Field $node)
    {
        $rawVariableValues = $this->getRawVariableValues();
        $fieldDef = $this->astFieldToFieldDef($node);
        if (null === $fieldDef) {
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
        return $this->getMaxQueryComplexity() > 0;
    }

    /**
     * Given a selectionSet, adds all of the fields in that selection to
     * the passed in map of fields, and returns it at the end.
     *
     * Note: This is not the same as execution's collectFields because at static
     * time we do not know what object type will be used, so we unconditionally
     * spread in all fragments.
     *
     * @see GraphQL\Validator\Rules\OverlappingFieldsCanBeMerged
     *
     * @param ValidationContext $context
     * @param Type|null         $parentType
     * @param SelectionSet      $selectionSet
     * @param \ArrayObject      $visitedFragmentNames
     * @param \ArrayObject      $astAndDefs
     *
     * @return \ArrayObject
     */
    private function collectFieldASTsAndDefs(ValidationContext $context, $parentType, SelectionSet $selectionSet, \ArrayObject $visitedFragmentNames = null, \ArrayObject $astAndDefs = null)
    {
        $_visitedFragmentNames = $visitedFragmentNames ?: new \ArrayObject();
        $_astAndDefs = $astAndDefs ?: new \ArrayObject();

        foreach ($selectionSet->selections as $selection) {
            switch ($selection->kind) {
                case Node::FIELD:
                    $fieldName = $selection->name->value;
                    $fieldDef = null;
                    if ($parentType && method_exists($parentType, 'getFields')) {
                        $tmp = $parentType->getFields();
                        if (isset($tmp[$fieldName])) {
                            $fieldDef = $tmp[$fieldName];
                        }
                    }
                    $responseName = $this->getFieldName($selection);
                    if (!isset($_astAndDefs[$responseName])) {
                        $_astAndDefs[$responseName] = new \ArrayObject();
                    }
                    $_astAndDefs[$responseName][] = [$selection, $fieldDef];
                    break;
                case Node::INLINE_FRAGMENT:
                    /* @var InlineFragment $inlineFragment */
                    $_astAndDefs = $this->collectFieldASTsAndDefs(
                        $context,
                        TypeInfo::typeFromAST($context->getSchema(), $selection->typeCondition),
                        $selection->selectionSet,
                        $_visitedFragmentNames,
                        $_astAndDefs
                    );
                    break;
                case Node::FRAGMENT_SPREAD:
                    /* @var FragmentSpread $selection */
                    $fragName = $selection->name->value;
                    if (!empty($_visitedFragmentNames[$fragName])) {
                        continue;
                    }
                    $_visitedFragmentNames[$fragName] = true;
                    $fragment = $context->getFragment($fragName);
                    if (!$fragment) {
                        continue;
                    }
                    $_astAndDefs = $this->collectFieldASTsAndDefs(
                        $context,
                        TypeInfo::typeFromAST($context->getSchema(), $fragment->typeCondition),
                        $fragment->selectionSet,
                        $_visitedFragmentNames,
                        $_astAndDefs
                    );
                    break;
            }
        }

        return $_astAndDefs;
    }
}
