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
use GraphQL\Language\AST\Node;
use GraphQL\Validator\ValidationContext;

class MaxQueryComplexity extends AbstractQuerySecurity
{
    const DEFAULT_QUERY_MAX_COMPLEXITY = 100;

    private static $maxQueryComplexity;

    public function __construct($maxQueryDepth = self::DEFAULT_QUERY_MAX_COMPLEXITY)
    {
        $this->setMaxQueryComplexity($maxQueryDepth);
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

    public function __invoke(ValidationContext $context)
    {
        $this->gatherFragmentDefinition($context);
        $this->gatherRootTypes($context);

        // is disabled?
        if (0 === $this->getMaxQueryComplexity()) {
            return [];
        }

        return [
            Node::FIELD => function (Field $node) use ($context) {

            },
        ];
    }
}
