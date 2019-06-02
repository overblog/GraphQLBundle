<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Executor;

use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Executor\Executor;
use PHPUnit\Framework\TestCase;

class ExecutorTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage PromiseAdapter should be an object instantiating "Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface" or "GraphQL\Executor\Promise\PromiseAdapter" with a "wait" method.
     */
    public function testInvalidExecutorAdapterPromise(): void
    {
        $schema = $this->getMockBuilder(Schema::class)->disableOriginalConstructor()->getMock();
        $executor = new Executor();
        $executor->execute(new ReactPromiseAdapter(), $schema, '');
    }
}
