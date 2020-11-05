<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition;

use LogicException;
use Overblog\GraphQLBundle\Definition\GraphQLServices;
use PHPUnit\Framework\TestCase;

class GraphQLServicesTest extends TestCase
{
    public function testGetUnknownService(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('GraphQL service "unknown" could not be located. You should define it.');
        (new GraphQLServices())->get('unknown');
    }
}
