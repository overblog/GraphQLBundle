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

use Overblog\GraphQLBundle\Request\Validator\Rule\QueryDepth;

class QueryDepthTest extends AbstractQuerySecurityTest
{
    /**
     * @param $max
     * @param $count
     *
     * @return string
     */
    protected function getErrorMessage($max, $count)
    {
        return QueryDepth::maxQueryDepthErrorMessage($max, $count);
    }

    /**
     * @param $maxDepth
     *
     * @return QueryDepth
     */
    protected function createRule($maxDepth)
    {
        return new QueryDepth($maxDepth);
    }

    /**
     * @param $queryDepth
     * @param int   $maxQueryDepth
     * @param array $expectedErrors
     * @dataProvider queryDataProvider
     */
    public function testSimpleQueries($queryDepth, $maxQueryDepth = 7, $expectedErrors = [])
    {
        $this->assertDocumentValidator($this->buildRecursiveQuery($queryDepth), $maxQueryDepth, $expectedErrors);
    }

    /**
     * @param $queryDepth
     * @param int   $maxQueryDepth
     * @param array $expectedErrors
     * @dataProvider queryDataProvider
     */
    public function testFragmentQueries($queryDepth, $maxQueryDepth = 7, $expectedErrors = [])
    {
        $this->assertDocumentValidator($this->buildRecursiveUsingFragmentQuery($queryDepth), $maxQueryDepth, $expectedErrors);
    }

    /**
     * @param $queryDepth
     * @param int   $maxQueryDepth
     * @param array $expectedErrors
     * @dataProvider queryDataProvider
     */
    public function testInlineFragmentQueries($queryDepth, $maxQueryDepth = 7, $expectedErrors = [])
    {
        $this->assertDocumentValidator($this->buildRecursiveUsingInlineFragmentQuery($queryDepth), $maxQueryDepth, $expectedErrors);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $maxQueryDepth argument must be greater or equal to 0.
     */
    public function testMaxQueryDepthMustBeGreaterOrEqualTo0()
    {
        $this->createRule(-1);
    }

    public function queryDataProvider()
    {
        return [
            [1], // Valid because depth under default limit (7)
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8, 9], // Valid because depth under new limit (9)
            [10, 0], // Valid because 0 depth disable limit
            [
                10,
                8,
                [$this->createFormattedError(8, 10)],
            ], // failed because depth over limit (8)
            [
                60,
                20,
                [$this->createFormattedError(20, 60)],
            ], // failed because depth over limit (20)
        ];
    }
}
