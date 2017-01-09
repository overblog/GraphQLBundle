<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests;

use GraphQL\GraphQL;
use GraphQL\Schema;
use GraphQL\Type\Definition\Config;
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

        $this->generateClasses();

        Config::enableValidation();

        Resolver::setHumanType($this->getType('Human'));
        Resolver::setDroidType($this->getType('Droid'));

        $this->schema = new Schema(['query' => $this->getType('Query')]);
    }

    /**
     * Helper function to test a query and the expected response.
     *
     * @param $query
     * @param $expected
     * @param null $params
     */
    protected function assertValidQuery($query, $expected, $params = null)
    {
        $result = GraphQL::execute($this->schema, $query, null, null, $params);

        $this->assertEquals(['data' => $expected], $result, json_encode($result));
    }
}
