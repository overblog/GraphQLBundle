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

use GraphQL\Language\AST\Field;
use GraphQL\Language\AST\FragmentDefinition;
use GraphQL\Language\AST\FragmentSpread;
use GraphQL\Language\AST\InlineFragment;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\SelectionSet;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Utils\TypeInfo;
use GraphQL\Validator\ValidationContext;

abstract class AbstractQuerySecurity
{
    const DISABLED = 0;

    /** @var FragmentDefinition[] */
    private $fragments = [];

    /** @var Type[]  */
    private $rootTypes = [];

    /**
     * @return \GraphQL\Language\AST\FragmentDefinition[]
     */
    protected function getFragments()
    {
        return $this->fragments;
    }

    /**
     * @return \GraphQL\Type\Definition\Type[]
     */
    protected function getRootTypes()
    {
        return $this->rootTypes;
    }

    /**
     * check if equal to 0 no check is done. Must be greater or equal to 0.
     *
     * @param $value
     */
    protected static function checkIfGreaterOrEqualToZero($name, $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(sprintf('$%s argument must be greater or equal to 0.', $name));
        }
    }

    protected function isParentRootType(ValidationContext $context)
    {
        $parentType = $context->getParentType();
        $isParentRootType = $parentType && in_array($parentType, $this->getRootTypes());

        return $isParentRootType;
    }

    protected function isIntrospectionType(ValidationContext $context)
    {
        $type = $this->retrieveCurrentType($context);
        $isIntrospectionType = $type && $type->name === '__Schema';

        return $isIntrospectionType;
    }

    protected function gatherRootTypes(ValidationContext $context)
    {
        $schema = $context->getSchema();
        $this->rootTypes = [$schema->getQueryType(), $schema->getMutationType(), $schema->getSubscriptionType()];
    }

    protected function gatherFragmentDefinition(ValidationContext $context)
    {
        // Gather all the fragment definition.
        // Importantly this does not include inline fragments.
        $definitions = $context->getDocument()->definitions;
        foreach ($definitions as $node) {
            if ($node instanceof FragmentDefinition) {
                $this->fragments[$node->name->value] = $node;
            }
        }
    }

    protected function retrieveCurrentType(ValidationContext $context)
    {
        $type = $context->getType();

        if ($type instanceof WrappingType) {
            $type = $type->getWrappedType(true);
        }

        return $type;
    }

    protected function getFragment(FragmentSpread $fragmentSpread)
    {
        $spreadName = $fragmentSpread->name->value;
        $fragments = $this->getFragments();

        return isset($fragments[$spreadName]) ? $fragments[$spreadName] : null;
    }

    protected function invokeIfNeeded(ValidationContext $context, array $validators)
    {
        $this->gatherFragmentDefinition($context);
        $this->gatherRootTypes($context);

        // is disabled?
        if (!$this->isEnabled()) {
            return [];
        }

        return $validators;
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
    protected function collectFieldASTsAndDefs(ValidationContext $context, $parentType, SelectionSet $selectionSet, \ArrayObject $visitedFragmentNames = null, \ArrayObject $astAndDefs = null)
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

    protected function getFieldName(Field $node)
    {
        $fieldName = $node->name->value;
        $responseName = $node->alias ? $node->alias->value : $fieldName;

        return $responseName;
    }

    abstract protected function isEnabled();
}
