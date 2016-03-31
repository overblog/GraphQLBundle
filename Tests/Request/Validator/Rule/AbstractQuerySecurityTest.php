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
use Overblog\GraphQLBundle\Request\Validator\Rule\AbstractQuerySecurity;

abstract class AbstractQuerySecurityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $max
     *
     * @return AbstractQuerySecurity
     */
    abstract protected function createRule($max);

    /**
     * @param $max
     * @param $count
     *
     * @return string
     */
    abstract protected function getErrorMessage($max, $count);

    public function testIgnoreIntrospectionQuery()
    {
        $this->assertDocumentValidator(Introspection::getIntrospectionQuery(true), 1);
    }

    protected function createFormattedError($max, $count)
    {
        return FormattedError::create($this->getErrorMessage($max, $count), [new SourceLocation(1, 17)]);
    }

    protected function buildRecursiveQuery($depth)
    {
        $query = sprintf('query MyQuery { human%s }', $this->buildRecursiveQueryPart($depth));

        return $query;
    }

    protected function buildRecursiveUsingFragmentQuery($depth)
    {
        $query = sprintf(
            'query MyQuery { human { ...F1 } } fragment F1 on Human %s',
            $this->buildRecursiveQueryPart($depth)
        );

        return $query;
    }

    protected function buildRecursiveUsingInlineFragmentQuery($depth)
    {
        $query = sprintf(
            'query MyQuery { human { ...on Human %s } }',
            $this->buildRecursiveQueryPart($depth)
        );

        return $query;
    }

    protected function buildRecursiveQueryPart($depth)
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

    protected function assertDocumentValidator($queryString, $max, array $expectedErrors = [])
    {
        $errors = DocumentValidator::validate(
            Schema::buildSchema(),
            Parser::parse($queryString),
            [$this->createRule($max)]
        );

        $this->assertEquals($expectedErrors, array_map(['GraphQL\Error', 'formatError'], $errors), $queryString);

        return $errors;
    }
}
