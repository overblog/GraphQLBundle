<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional;

final class BootTest extends TestCase
{
    public function testBootAppKernel(): void
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $this->assertTrue($kernel->isBooted());
    }
}
