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
use GraphQL\Validator\ValidationContext;

class MaxQueryDepth
{
    const DEFAULT_QUERY_MAX_DEPTH = 7;

    private $maxQueryDepth;

    private $fragments = [];

    public function __construct($maxQueryDepth = self::DEFAULT_QUERY_MAX_DEPTH)
    {
        $this->maxQueryDepth = $maxQueryDepth;
    }

    public function setMaxQueryDepth($maxQueryDepth)
    {
        $this->maxQueryDepth = (int) $maxQueryDepth;

        return $this;
    }

    public static function maxQueryDepthErrorMessage($max)
    {
        return sprintf('Max query depth %d is reached.', $max);
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

        return [
            Node::FIELD => function (Field $astField) {
                $depth = $astField->selectionSet ? $this->countQueryDepth($astField->selectionSet, $this->maxQueryDepth + 1) : 0;

                if ($depth > $this->maxQueryDepth) {
                    return new Error(static::maxQueryDepthErrorMessage($this->maxQueryDepth), [$astField]);
                }
            },
        ];
    }

    private function countQueryDepth(SelectionSet $selectionSet, $stopCountingAt, $depth = 1)
    {
        foreach ($selectionSet->selections as $selectionAST) {
            if ($depth >= $stopCountingAt) {
                break;
            }

            if ($selectionAST instanceof Field || $selectionAST instanceof InlineFragment) {
                $depth = $this->countFieldDepth($selectionAST->selectionSet, $stopCountingAt, $depth);
            } elseif ($selectionAST instanceof FragmentSpread) {
                $depth = $this->countFragmentDepth($selectionAST, $stopCountingAt, $depth);
            }
        }

        return $depth;
    }

    private function countFieldDepth(SelectionSet $selectionSet = null, $stopCountingAt, $depth = 1)
    {
        if (null !== $selectionSet) {
            return $this->countQueryDepth($selectionSet, $stopCountingAt, ++$depth);
        }

        return $depth;
    }

    private function countFragmentDepth(FragmentSpread $selectionAST, $stopCountingAt, $depth = 1)
    {
        $spreadName = $selectionAST->name->value;
        if (isset($this->fragments[$spreadName])) {
            /** @var FragmentDefinition $fragment */
            $fragment = $this->fragments[$spreadName];
            $depth = $this->countQueryDepth($fragment->selectionSet, $stopCountingAt, $depth);
        }

        return $depth;
    }
}
