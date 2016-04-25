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
use GraphQL\Schema;
use GraphQL\Type\Definition\Config;
use Overblog\GraphQLGenerator\Generator\TypeGenerator;
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

        Config::enableValidation();

        Resolver::setHumanType($this->getType('Human'));
        Resolver::setDroidType($this->getType('Droid'));

        $this->schema = new Schema($this->getType('Query'));
    }

    /**
     * Helper function to test a query and the expected response.
     */
    protected function assertValidQuery($query, $expected, $params = null)
    {
        $result = GraphQL::execute($this->schema, $query, null, $params);

        $this->assertEquals(['data' => $expected], $result, json_encode($result));
    }
}
