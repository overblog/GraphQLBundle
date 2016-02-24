<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Overblog\GraphQLBundle\Relay\Node;

use Overblog\GraphQLBundle\Relay\Node\GlobalId;

class GlobalIdTest extends \PHPUnit_Framework_TestCase
{
    public function testToGlobalId()
    {
        $globalId = GlobalId::toGlobalId('User', 15);

        $this->assertEquals(sprintf('User%s15', GlobalId::SEPARATOR), base64_decode($globalId));
    }

    public function testToGlobalIdWithTypeEmpty()
    {
        $globalId = GlobalId::toGlobalId('', 15);

        $this->assertEquals(sprintf('%s15', GlobalId::SEPARATOR), base64_decode($globalId));
    }

    public function testToGlobalIdWithIdEmpty()
    {
        $globalId = GlobalId::toGlobalId('User', null);

        $this->assertEquals(sprintf('User%s', GlobalId::SEPARATOR), base64_decode($globalId));
    }

    public function testToGlobalIdWithTypeAndIdEmpty()
    {
        $globalId = GlobalId::toGlobalId(null, null);

        $this->assertEquals(sprintf('%s', GlobalId::SEPARATOR), base64_decode($globalId));
    }

    public function testFromGlobalId()
    {
        $params = GlobalId::fromGlobalId('VXNlcjoxNQ==');

        $this->assertEquals(['type' => 'User', 'id' => 15], $params);
    }

    public function testFromGlobalIdWithTypeEmpty()
    {
        $params = GlobalId::fromGlobalId('OjE1=');

        $this->assertEquals(['type' => null, 'id' => 15], $params);
    }

    public function testFromGlobalIdWithIdEmpty()
    {
        $params = GlobalId::fromGlobalId('VXNlcjo=');

        $this->assertEquals(['type' => 'User', 'id' => null], $params);
    }

    public function testFromGlobalIdWithTypeAndIdEmpty()
    {
        $params = GlobalId::fromGlobalId('Og==');

        $this->assertEquals(['type' => null, 'id' => null], $params);
    }
}
