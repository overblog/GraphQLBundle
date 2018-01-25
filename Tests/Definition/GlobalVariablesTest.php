<?php

namespace Overblog\GraphQLBundle\Tests\Definition;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use PHPUnit\Framework\TestCase;

class GlobalVariablesTest extends TestCase
{
    public function testGetUnknownService()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Global variable "unknown" could not be located. You should define it.');
        (new GlobalVariables())->get('unknown');
    }
}
