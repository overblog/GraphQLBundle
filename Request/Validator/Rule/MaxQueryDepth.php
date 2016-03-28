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
        return $this->invokeIfNeeded(
            $context,
            [
                Node::FIELD => function (Field $node) use ($context) {
                    // check depth only on first rootTypes children and ignore check on introspection query
                    if ($this->isParentRootType($context) && !$this->isIntrospectionType($context)) {
                        $maxDepth = $this->nodeMaxDepth($node);

                        if ($maxDepth > $this->getMaxQueryDepth()) {
                            return new Error($this->maxQueryDepthErrorMessage($this->getMaxQueryDepth(), $maxDepth), [$node]);
                        }
                    }
                },
            ]
        );
    }

    protected function isEnabled()
    {
        return $this->getMaxQueryDepth() > 0;
    }

    private function nodeMaxDepth(Node $node, $depth = 1, $maxDepth = 1)
    {
        if (!isset($node->selectionSet)) {
            return $maxDepth;
        }

        foreach ($node->selectionSet->selections as $childNode) {
            switch ($childNode->kind) {
                case Node::FIELD:
                    // node has children?
                    if (null !== $childNode->selectionSet) {
                        // update maxDepth if needed
                        if ($depth > $maxDepth) {
                            $maxDepth = $depth;
                        }
                        $maxDepth = $this->nodeMaxDepth($childNode, $depth + 1, $maxDepth);
                    }
                    break;

                case Node::INLINE_FRAGMENT:
                    // node has children?
                    if (null !== $childNode->selectionSet) {
                        $maxDepth = $this->nodeMaxDepth($childNode, $depth, $maxDepth);
                    }
                    break;

                case Node::FRAGMENT_SPREAD:
                    $fragment = $this->getFragment($childNode);

                    if (null !== $fragment) {
                        $maxDepth = $this->nodeMaxDepth($fragment, $depth, $maxDepth);
                    }
                    break;
            }
        }

        return $maxDepth;
    }
}
