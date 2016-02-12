<?php

namespace Overblog\GraphBundle\Tests\Functional;


class BootTest extends TestCase
{
    public function testBoot()
    {
        $kernel = $this->createKernel();
        $kernel->boot();
    }
}
