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
use GraphQL\Language\AST\FragmentSpread;
use GraphQL\Language\AST\InlineFragment;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\SelectionSet;
use GraphQL\Validator\ValidationContext;

class MaxQueryDepth extends AbstractQuerySecurity
{
    const DEFAULT_QUERY_MAX_DEPTH = 100;

    /**
     * @var int
     */
    private static $maxQueryDepth;

    public function __construct($maxQueryDepth = self::DEFAULT_QUERY_MAX_DEPTH)
    {
        $this->setMaxQueryDepth($maxQueryDepth);
    }

    /**
     * Set max query depth. If equal to 0 no check is done. Must be greater or equal to 0.
     *
     * @param $maxQueryDepth
     */
    public static function setMaxQueryDepth($maxQueryDepth)
    {
        self::checkIfGreaterOrEqualToZero('maxQueryDepth', $maxQueryDepth);

        self::$maxQueryDepth = (int) $maxQueryDepth;
    }

    public static function getMaxQueryDepth()
    {
        return self::$maxQueryDepth;
    }

    public static function maxQueryDepthErrorMessage($max, $count)
    {
        return sprintf('Max query depth should be %d but got %d.', $max, $count);
    }

    public function __invoke(ValidationContext $context)
    {
        $this->gatherFragmentDefinition($context);
        $this->gatherRootTypes($context);

        // is disabled?
        if (0 === $this->getMaxQueryDepth()) {
            return [];
        }

        return [
            Node::FIELD => function (Field $node) use ($context) {
                // check depth only on first rootTypes children and ignore check on introspection query
                if ($this->isRootType($context) && !$this->isIntrospectionType($context)) {
                    $depth = $node->selectionSet ? $this->countNodeDepth($node, 0, true) : 0;

                    if ($depth > $this->getMaxQueryDepth()) {
                        return new Error($this->maxQueryDepthErrorMessage($this->getMaxQueryDepth(), $depth), [$node]);
                    }
                }
            },
        ];
    }

    protected function countNodeDepth(Node $parentNode, $depth = 0, $resetDepthForEachSelection = false)
    {
        if (!isset($parentNode->selectionSet)) {
            return $depth;
        }

        foreach ($parentNode->selectionSet->selections as $node) {
            $depth = $resetDepthForEachSelection ? 0 : $depth;

            $type = $this->getNodeType($node);
            if (null === $type) {
                continue;
            }

            $method = 'count'.$type.'Depth';

            $depth = $this->$method($node, $depth);
        }

        return $depth;
    }

    private function countFieldDepth(Field $field, $depth)
    {
        $selectionSet = $field->selectionSet;

        return null === $selectionSet ? $depth : $this->countNodeDepth($field, ++$depth);
    }

    private function countInlineFragmentDepth(InlineFragment $inlineFragment, $depth)
    {
        $selectionSet = $inlineFragment->selectionSet;

        return null === $selectionSet ? $depth : $this->countNodeDepth($inlineFragment, $depth);
    }

    private function countFragmentSpreadDepth(FragmentSpread $fragmentSpread, $depth)
    {
        $spreadName = $fragmentSpread->name->value;
        $fragments = $this->getFragments();

        if (isset($fragments[$spreadName])) {
            $fragment = $fragments[$spreadName];
            $depth = $this->countNodeDepth($fragment, $depth);
        }

        return $depth;
    }
}
