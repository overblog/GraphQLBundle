<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Resolver;

use Overblog\GraphQLBundle\Request\Executor;

class ExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage At least one schema should be declare.
     */
    public function testGetSchemaNoSchemaFound()
    {
        (new Executor())->getSchema('default');
    }
}
