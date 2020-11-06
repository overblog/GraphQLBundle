<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition;

use LogicException;
use Overblog\GraphQLBundle\Definition\GlobalVariables;
use PHPUnit\Framework\TestCase;

class GlobalVariablesTest extends TestCase
{
    public function testGetUnknownService(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Global variable "unknown" could not be located. You should define it.');
        (new GlobalVariables())->get('unknown');
    }
}
