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
use GraphQL\Language\AST\OperationDefinition;
use GraphQL\Language\AST\SelectionSet;
use GraphQL\Validator\ValidationContext;

class QueryDepth extends AbstractQuerySecurity
{
    const DEFAULT_QUERY_MAX_DEPTH = self::DISABLED;

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
        return $this->invokeIfNeeded(
            $context,
            [
                Node::OPERATION_DEFINITION => [
                    'leave' => function (OperationDefinition $operationDefinition) use ($context) {
                        $maxDepth = $this->fieldDepth($operationDefinition);

                        if ($maxDepth > $this->getMaxQueryDepth()) {
                            return new Error($this->maxQueryDepthErrorMessage($this->getMaxQueryDepth(), $maxDepth));
                        }
                    },
                ],
            ]
        );
    }

    protected function isEnabled()
    {
        return $this->getMaxQueryDepth() !== static::DISABLED;
    }

    private function fieldDepth($node, $depth = 0, $maxDepth = 0)
    {
        if (isset($node->selectionSet) && $node->selectionSet instanceof SelectionSet) {
            foreach ($node->selectionSet->selections as $childNode) {
                $maxDepth = $this->nodeDepth($childNode, $depth, $maxDepth);
            }
        }

        return $maxDepth;
    }

    private function nodeDepth(Node $node, $depth = 0, $maxDepth = 0)
    {
        switch ($node->kind) {
            case Node::FIELD:
                /* @var Field $node */
                // node has children?
                if (null !== $node->selectionSet) {
                    // update maxDepth if needed
                    if ($depth > $maxDepth) {
                        $maxDepth = $depth;
                    }
                    $maxDepth = $this->fieldDepth($node, $depth + 1, $maxDepth);
                }
                break;

            case Node::INLINE_FRAGMENT:
                /* @var InlineFragment $node */
                // node has children?
                if (null !== $node->selectionSet) {
                    $maxDepth = $this->fieldDepth($node, $depth, $maxDepth);
                }
                break;

            case Node::FRAGMENT_SPREAD:
                /* @var FragmentSpread $node */
                $fragment = $this->getFragment($node);

                if (null !== $fragment) {
                    $maxDepth = $this->fieldDepth($fragment, $depth, $maxDepth);
                }
                break;
        }

        return $maxDepth;
    }
}
