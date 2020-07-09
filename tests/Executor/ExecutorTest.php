<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Executor;

use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Executor\Executor;
use Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function sprintf;

class ExecutorTest extends TestCase
{
    public function testInvalidExecutorAdapterPromise(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'PromiseAdapter should be an object instantiating "%s" or "%s" with a "wait" method.',
            PromiseAdapterInterface::class,
            PromiseAdapter::class
            ));
        $schema = $this->getMockBuilder(Schema::class)->disableOriginalConstructor()->getMock();
        $executor = new Executor();
        $executor->execute(new ReactPromiseAdapter(), $schema, '');
    }
}
