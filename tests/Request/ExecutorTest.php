<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Request;

use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Executor\Executor;
use Overblog\GraphQLBundle\Request\Executor as RequestExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ExecutorTest extends TestCase
{
    protected function getMockedExecutor(): RequestExecutor
    {
        /** @var EventDispatcher&MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->setMethods(['dispatch'])->getMock();

        return new RequestExecutor(new Executor(), new ReactPromiseAdapter(), $dispatcher);
    }

    public function testGetSchemaNoSchemaFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('At least one schema should be declare.');

        $this->getMockedExecutor()->getSchema('fake');
    }

    public function testGetSchemasName(): void
    {
        $executor = $this->getMockedExecutor();
        $executor->addSchemaBuilder('schema1', function (): void {
        });
        $executor->addSchema('schema2', new Schema([]));

        $this->assertSame($executor->getSchemasNames(), ['schema1', 'schema2']);
    }
}
