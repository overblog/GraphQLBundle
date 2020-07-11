<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder;
use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder as LegacyConnectionBuilder;

/**
 * @group legacy
 */
class ConnectionBuilderTest extends \Overblog\GraphQLBundle\Tests\Relay\Connection\ConnectionBuilderTest
{
    public function testGetOffsetWithDefault(): void
    {
        $this->assertSame(
            (new ConnectionBuilder())->getOffsetWithDefault(null, 15),
            LegacyConnectionBuilder::getOffsetWithDefault(null, 15)
        );
    }

    public function testOffsetToCursor(): void
    {
        $this->assertSame(
            (new ConnectionBuilder())->offsetToCursor(15),
            LegacyConnectionBuilder::offsetToCursor(15)
        );
    }

    public function testCursorToOffset(): void
    {
        $this->assertSame(
            (new ConnectionBuilder())->cursorToOffset('YXJyYXljb25uZWN0aW9uOjE1'),
            LegacyConnectionBuilder::cursorToOffset('YXJyYXljb25uZWN0aW9uOjE1')
        );
    }

    public static function getBuilder(): string
    {
        return LegacyConnectionBuilder::class;
    }
}
