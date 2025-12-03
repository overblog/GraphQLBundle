<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection;

use Overblog\GraphQLBundle\Relay\Connection\TotalCountCache;
use PHPUnit\Framework\TestCase;

class TotalCountCacheTest extends TestCase
{
    public function testCacheCallable(): void
    {
        $total = $this->createMock(TotalCountCache::class);
        $total
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(7);

        $cache = new TotalCountCache($total);

        $this->assertEquals(7, $cache());
        $this->assertEquals(7, $cache());
    }

    public function testCacheInt(): void
    {
        $cache = new TotalCountCache(12);

        $this->assertEquals(12, $cache());
    }

    public function testReset(): void
    {
        $total = $this->createMock(TotalCountCache::class);
        $total
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturn(7);

        $cache = new TotalCountCache($total);

        $this->assertEquals(7, $cache());
        $cache->reset();
        $this->assertEquals(7, $cache());
    }
}
