<?php

namespace Overblog\GraphQLBundle\Tests\Relay\Node;

use Overblog\GraphQLBundle\Relay\Node\GlobalId;
use PHPUnit\Framework\TestCase;

class GlobalIdTest extends TestCase
{
    public function testToGlobalId()
    {
        $globalId = GlobalId::toGlobalId('User', '15');

        $this->assertSame(\sprintf('User%s15', GlobalId::SEPARATOR), \base64_decode($globalId));
    }

    public function testToGlobalIdWithTypeEmpty()
    {
        $globalId = GlobalId::toGlobalId('', '15');

        $this->assertSame(\sprintf('%s15', GlobalId::SEPARATOR), \base64_decode($globalId));
    }

    public function testToGlobalIdWithIdEmpty()
    {
        $globalId = GlobalId::toGlobalId('User', null);

        $this->assertSame(\sprintf('User%s', GlobalId::SEPARATOR), \base64_decode($globalId));
    }

    public function testToGlobalIdWithTypeAndIdEmpty()
    {
        $globalId = GlobalId::toGlobalId(null, null);

        $this->assertSame(\sprintf('%s', GlobalId::SEPARATOR), \base64_decode($globalId));
    }

    public function testFromGlobalId()
    {
        $params = GlobalId::fromGlobalId(\base64_encode('User:15'));

        $this->assertSame(['type' => 'User', 'id' => '15'], $params);
    }

    public function testFromGlobalIdWithTypeEmpty()
    {
        $params = GlobalId::fromGlobalId(\base64_encode(':15'));

        $this->assertSame(['type' => null, 'id' => '15'], $params);
    }

    public function testFromGlobalIdWithIdEmpty()
    {
        $params = GlobalId::fromGlobalId(\base64_encode('User:'));

        $this->assertSame(['type' => 'User', 'id' => null], $params);
    }

    public function testFromGlobalIdWithTypeAndIdEmpty()
    {
        $params = GlobalId::fromGlobalId(\base64_encode(':'));

        $this->assertSame(['type' => null, 'id' => null], $params);
    }

    public function testFromGlobalIdWithNotBase64Entry()
    {
        $params = GlobalId::fromGlobalId(1);

        $this->assertSame(['type' => null, 'id' => null], $params);
    }
}
