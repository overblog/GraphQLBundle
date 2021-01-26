<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition;

use LogicException;
use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Overblog\GraphQLBundle\Resolver\MutationResolver;
use Overblog\GraphQLBundle\Resolver\QueryResolver;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use PHPUnit\Framework\TestCase;

class GraphQLServicesTest extends TestCase
{
    public function testGetUnknownService(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('GraphQL service "unknown" could not be located. You should define it.');
        $services = new GraphQLServices(
            $this->createMock(TypeResolver::class),
            $this->createMock(QueryResolver::class),
            $this->createMock(MutationResolver::class),
            []
        );

        $services->get('unknown');
    }
}
