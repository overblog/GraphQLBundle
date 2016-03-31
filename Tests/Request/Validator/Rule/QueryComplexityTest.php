<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Request\Validator\Rule;

use Overblog\GraphQLBundle\Request\Validator\Rule\QueryComplexity;

class QueryComplexityTest extends AbstractQuerySecurityTest
{
    /**
     * @param $max
     * @param $count
     *
     * @return string
     */
    protected function getErrorMessage($max, $count)
    {
        return QueryComplexity::maxQueryComplexityErrorMessage($max, $count);
    }

    /**
     * @param $maxDepth
     *
     * @return QueryComplexity
     */
    protected function createRule($maxDepth)
    {
        return new QueryComplexity($maxDepth);
    }
}
