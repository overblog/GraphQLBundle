<?php

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Overblog\GraphQLGenerator\Tests\Generator\AbstractTypeGeneratorTest;

abstract class AbstractStarWarsTest extends AbstractTypeGeneratorTest
{
    /**
     * @var Schema
     */
    protected $schema;

    public function setUp()
    {
        parent::setUp();

        $this->classLoader->setPsr4('GraphQL\\Tests\\', __DIR__ . '/../vendor/webonyx/graphql-php/tests');

        $this->generateClasses();

        Resolver::setHumanType($this->getType('Human'));
        Resolver::setDroidType($this->getType('Droid'));

        $this->schema = new Schema(['query' => $this->getType('Query')]);
        $this->schema->assertValid();
    }

    /**
     * Helper function to test a query and the expected response.
     * @param $query
     * @param $expected
     * @param null $variables
     */
    protected function assertValidQuery($query, $expected, $variables = null)
    {
        $actual = GraphQL::executeQuery($this->schema, $query, null, null, $variables)->toArray();
        $expected = ['data' => $expected];
        $this->assertEquals($expected, $actual, json_encode($actual));
    }

    protected function sortSchemaEntry(array &$entries, $entryKey, $sortBy)
    {
        if (isset($entries['data']['__schema'][$entryKey])) {
            $data = &$entries['data']['__schema'][$entryKey];
        } else {
            $data = &$entries['__schema'][$entryKey];
        }

        usort($data, function ($data1, $data2) use ($sortBy) {
            return strcmp($data1[$sortBy], $data2[$sortBy]);
        });
    }
}
