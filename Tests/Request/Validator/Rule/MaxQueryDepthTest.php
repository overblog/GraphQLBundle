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

use GraphQL\FormattedError;
use GraphQL\Language\Parser;
use GraphQL\Language\SourceLocation;
use GraphQL\Type\Introspection;
use GraphQL\Validator\DocumentValidator;
use Overblog\GraphQLBundle\Request\Validator\Rule\MaxQueryDepth;

class MaxQueryDepthTest extends \PHPUnit_Framework_TestCase
{
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

    public function testIgnoreIntrospectionQuery()
    {
        $this->assertDocumentValidator(Introspection::getIntrospectionQuery(true), 1);
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
            [
                10,
                8,
                [$this->createFormattedError(8, 10)],
            ], // failed because depth over limit (8)
            [
                60,
                8,
                [$this->createFormattedError(8, 58)],
            ], // failed because depth over limit (8) and stop count at 58
        ];
    }

    private function createFormattedError($max, $count)
    {
        return FormattedError::create(MaxQueryDepth::maxQueryDepthErrorMessage($max, $count), [new SourceLocation(1, 17)]);
    }

    private function buildRecursiveQuery($depth)
    {
        $query = sprintf('query MyQuery { human%s }', $this->buildRecursiveQueryPart($depth));

        return $query;
    }

    private function buildRecursiveUsingFragmentQuery($depth)
    {
        $query = sprintf(
            'query MyQuery { human { ...F1 } } fragment F1 on Human %s',
            $this->buildRecursiveQueryPart($depth)
        );

        return $query;
    }
    private function buildRecursiveUsingInlineFragmentQuery($depth)
    {
        $query = sprintf(
            'query MyQuery { human { ...on Human %s } }',
            $this->buildRecursiveQueryPart($depth)
        );

        return $query;
    }

    private function buildRecursiveQueryPart($depth)
    {
        $templates = [
            'human' => ' { firstName%s } ',
            'dog' => ' dog { name%s } ',
        ];

        $part = $templates['human'];

        for ($i = 1; $i <= $depth; ++$i) {
            $key = ($i % 2 == 1) ? 'human' : 'dog';
            $template = $templates[$key];

            $part = sprintf($part, ('human' == $key ? ' owner ' : '').$template);
        }
        $part = str_replace('%s', '', $part);

        return $part;
    }

    private function assertDocumentValidator($queryString, $depth, array $expectedErrors = [])
    {
        $errors = DocumentValidator::validate(
            Schema::buildSchema(),
            Parser::parse($queryString),
            [new MaxQueryDepth($depth)]
        );

        $this->assertEquals($expectedErrors, array_map(['GraphQL\Error', 'formatError'], $errors), $queryString);

        return $errors;
    }
}
