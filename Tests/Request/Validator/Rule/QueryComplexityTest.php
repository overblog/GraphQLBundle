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

    public function testSimpleQueries()
    {
        $query = 'query MyQuery { human { firstName } }';

        $this->assertDocumentValidators($query, 2, 3);
    }

    public function testInlineFragmentQueries()
    {
        $query = 'query MyQuery { human { ... on Human { firstName } } }';

        $this->assertDocumentValidators($query, 2, 3);
    }

    public function testFragmentQueries()
    {
        $query = 'query MyQuery { human { ...F1 } } fragment F1 on Human { firstName}';

        $this->assertDocumentValidators($query, 2, 3);
    }

    public function testAliasesQueries()
    {
        $query = 'query MyQuery { thomas: human(name: "Thomas") { firstName } jeremy: human(name: "Jeremy") { firstName } }';

        $this->assertDocumentValidators($query, 4, 5);
    }

    public function testCustomComplexityQueries()
    {
        $query = 'query MyQuery { human { dogs { name } } }';

        $this->assertDocumentValidators($query, 12, 13);
    }

    public function testCustomComplexityWithArgsQueries()
    {
        $query = 'query MyQuery { human { dogs(name: "Root") { name } } }';

        $this->assertDocumentValidators($query, 3, 4);
    }

    public function testCustomComplexityWithVariablesQueries()
    {
        $query = 'query MyQuery($dog: String!) { human { dogs(name: $dog) { name } } }';

        QueryComplexity::setRawVariableValues(['dog' => 'Roots']);

        $this->assertDocumentValidators($query, 3, 4);
    }

    public function testComplexityIntrospectionQuery()
    {
        $this->assertIntrospectionQuery(109);
    }

    public function testIntrospectionTypeMetaFieldQuery()
    {
        $this->assertIntrospectionTypeMetaFieldQuery(2);
    }

    public function testTypeNameMetaFieldQuery()
    {
        $this->assertTypeNameMetaFieldQuery(3);
    }

    private function assertDocumentValidators($query, $queryComplexity, $startComplexity)
    {
        for ($maxComplexity = $startComplexity; $maxComplexity >= 0; --$maxComplexity) {
            $positions = [];

            if ($maxComplexity < $queryComplexity && $maxComplexity !== QueryComplexity::DISABLED) {
                $positions = [$this->createFormattedError($maxComplexity, $queryComplexity)];
            }

            $this->assertDocumentValidator($query, $maxComplexity, $positions);
        }
    }
}
