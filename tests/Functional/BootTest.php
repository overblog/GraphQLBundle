<?php

namespace Overblog\GraphQLBundle\Tests\Functional;

class BootTest extends TestCase
{
    public function testBootAppKernel()
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $this->assertTrue($kernel->isBooted());
    }
}
