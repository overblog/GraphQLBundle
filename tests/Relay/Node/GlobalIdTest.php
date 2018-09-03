<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Node;

use Overblog\GraphQLBundle\Relay\Node\GlobalId;
use PHPUnit\Framework\TestCase;

class GlobalIdTest extends TestCase
{
    public function testToGlobalId(): void
    {
        $globalId = GlobalId::toGlobalId('User', 15);

        $this->assertEquals(\sprintf('User%s15', GlobalId::SEPARATOR), \base64_decode($globalId));
    }

    public function testToGlobalIdWithTypeEmpty(): void
    {
        $globalId = GlobalId::toGlobalId('', 15);

        $this->assertEquals(\sprintf('%s15', GlobalId::SEPARATOR), \base64_decode($globalId));
    }

    public function testToGlobalIdWithIdEmpty(): void
    {
        $globalId = GlobalId::toGlobalId('User', null);

        $this->assertEquals(\sprintf('User%s', GlobalId::SEPARATOR), \base64_decode($globalId));
    }

    public function testToGlobalIdWithTypeAndIdEmpty(): void
    {
        $globalId = GlobalId::toGlobalId(null, null);

        $this->assertEquals(\sprintf('%s', GlobalId::SEPARATOR), \base64_decode($globalId));
    }

    public function testFromGlobalId(): void
    {
        $params = GlobalId::fromGlobalId(\base64_encode('User:15'));

        $this->assertEquals(['type' => 'User', 'id' => 15], $params);
    }

    public function testFromGlobalIdWithTypeEmpty(): void
    {
        $params = GlobalId::fromGlobalId(\base64_encode(':15'));

        $this->assertEquals(['type' => null, 'id' => 15], $params);
    }

    public function testFromGlobalIdWithIdEmpty(): void
    {
        $params = GlobalId::fromGlobalId(\base64_encode('User:'));

        $this->assertEquals(['type' => 'User', 'id' => null], $params);
    }

    public function testFromGlobalIdWithTypeAndIdEmpty(): void
    {
        $params = GlobalId::fromGlobalId(\base64_encode(':'));

        $this->assertEquals(['type' => null, 'id' => null], $params);
    }

    public function testFromGlobalIdWithNotBase64Entry(): void
    {
        $params = GlobalId::fromGlobalId(1);

        $this->assertEquals(['type' => null, 'id' => null], $params);
    }
}
