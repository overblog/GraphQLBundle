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
use GraphQL\Language\AST\Field;
use GraphQL\Language\AST\FragmentDefinition;
use GraphQL\Language\AST\FragmentSpread;
use GraphQL\Language\AST\InlineFragment;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\SelectionSet;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Validator\ValidationContext;

class MaxQueryDepth
{
    const DEFAULT_QUERY_MAX_DEPTH = 100;
    const DEFAULT_MAX_COUNT_AFTER_DEPTH_LIMIT = 50;

    private static $maxQueryDepth;

    private $fragments = [];

    public function __construct($maxQueryDepth = self::DEFAULT_QUERY_MAX_DEPTH)
    {
        $this->setMaxQueryDepth($maxQueryDepth);
    }

    public static function setMaxQueryDepth($maxQueryDepth)
    {
        self::$maxQueryDepth = (int) $maxQueryDepth;
    }

    public static function maxQueryDepthErrorMessage($max, $count)
    {
        return sprintf('Max query depth should be %d but is greater or equal to %d.', $max, $count);
    }

    public function __invoke(ValidationContext $context)
    {
        // Gather all the fragment definition.
        // Importantly this does not include inline fragments.
        $definitions = $context->getDocument()->definitions;
        foreach ($definitions as $node) {
            if ($node instanceof FragmentDefinition) {
                $this->fragments[$node->name->value] = $node;
            }
        }
        $schema = $context->getSchema();
        $rootTypes = [$schema->getQueryType(), $schema->getMutationType(), $schema->getSubscriptionType()];

        return [
            Node::FIELD => $this->getFieldClosure($context, $rootTypes),
        ];
    }

    private function getFieldClosure(ValidationContext $context, array $rootTypes)
    {
        return function (Field $node) use ($context, $rootTypes) {
            $parentType = $context->getParentType();
            $type = $this->retrieveCurrentTypeFromValidationContext($context);
            $isIntrospectionType = $type && $type->name === '__Schema';
            $isParentRootType = $parentType && in_array($parentType, $rootTypes);

            // check depth only on first rootTypes children and ignore check on introspection query
            if ($isParentRootType && !$isIntrospectionType) {
                $depth = $node->selectionSet ?
                    $this->countSelectionDepth(
                        $node->selectionSet,
                        self::$maxQueryDepth + static::DEFAULT_MAX_COUNT_AFTER_DEPTH_LIMIT,
                        0,
                        true
                    ) :
                    0
                ;

                if ($depth > self::$maxQueryDepth) {
                    return new Error(static::maxQueryDepthErrorMessage(self::$maxQueryDepth, $depth), [$node]);
                }
            }
        };
    }

    private function retrieveCurrentTypeFromValidationContext(ValidationContext $context)
    {
        $type = $context->getType();

        if ($type instanceof WrappingType) {
            $type = $type->getWrappedType(true);
        }

        return $type;
    }

    private function countSelectionDepth(SelectionSet $selectionSet, $stopCountingAt, $depth = 0, $resetDepthForEachSelection = false)
    {
        foreach ($selectionSet->selections as $selectionAST) {
            if ($depth >= $stopCountingAt) {
                break;
            }

            $depth = $resetDepthForEachSelection ? 0 : $depth;

            if ($selectionAST instanceof Field) {
                $depth = $this->countFieldDepth($selectionAST->selectionSet, $stopCountingAt, $depth);
            } elseif ($selectionAST instanceof FragmentSpread) {
                $depth = $this->countFragmentDepth($selectionAST, $stopCountingAt, $depth);
            } elseif ($selectionAST instanceof InlineFragment) {
                $depth = $this->countInlineFragmentDepth($selectionAST->selectionSet, $stopCountingAt, $depth);
            }
        }

        return $depth;
    }

    private function countFieldDepth(SelectionSet $selectionSet = null, $stopCountingAt, $depth)
    {
        return null === $selectionSet ? $depth : $this->countSelectionDepth($selectionSet, $stopCountingAt, ++$depth);
    }

    private function countInlineFragmentDepth(SelectionSet $selectionSet = null, $stopCountingAt, $depth)
    {
        return null === $selectionSet ? $depth : $this->countSelectionDepth($selectionSet, $stopCountingAt, $depth);
    }

    private function countFragmentDepth(FragmentSpread $selectionAST, $stopCountingAt, $depth)
    {
        $spreadName = $selectionAST->name->value;
        if (isset($this->fragments[$spreadName])) {
            /** @var FragmentDefinition $fragment */
            $fragment = $this->fragments[$spreadName];
            $depth = $this->countSelectionDepth($fragment->selectionSet, $stopCountingAt, $depth);
        }

        return $depth;
    }
}
