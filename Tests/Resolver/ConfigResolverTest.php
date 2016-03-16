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

use Overblog\GraphQLBundle\Resolver\ConfigResolver;

class ConfigResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ConfigResolver */
    private $configResolver;

    public function setUp()
    {
        $this->configResolver = new ConfigResolver();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Config must be an array or implement \ArrayAccess interface
     */
    public function testConfigNotArrayOrImplementArrayAccess()
    {
        $this->configResolver->resolve('Not Array');
    }
}
