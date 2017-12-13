<?php

namespace Overblog\GraphQLBundle\Tests\Request;

use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Executor\Executor;
use Overblog\GraphQLBundle\Request\Executor as RequestExecutor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ExecutorTest extends TestCase
{
    /** @var RequestExecutor */
    private $executor;

    /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject */
    private $dispatcher;

    private $request = ['query' => 'query debug{ myField }', 'variables' => [], 'operationName' => null];

    public function setUp()
    {
        $this->dispatcher = $this->getMockBuilder(EventDispatcher::class)->setMethods(['dispatch'])->getMock();
        $this->dispatcher->expects($this->any())->method('dispatch')->willReturnArgument(1);

        $this->executor = new RequestExecutor(new Executor(), $this->dispatcher);
        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => [
                'myField' => [
                    'type' => Type::boolean(),
                    'resolve' => function () {
                        return false;
                    },
                ],
            ],
        ]);
        $this->executor->addSchema('global', new Schema(['query' => $queryType]));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Execution result should be an object instantiating "GraphQL\Executor\ExecutionResult".
     */
    public function testInvalidExecutorReturnNotObject()
    {
        $this->executor->setExecutor($this->createExecutorExecuteMock(false));
        $this->executor->execute(null, $this->request);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Execution result should be an object instantiating "GraphQL\Executor\ExecutionResult".
     */
    public function testInvalidExecutorReturnInvalidObject()
    {
        $this->executor->setExecutor($this->createExecutorExecuteMock(new \stdClass()));
        $this->executor->execute(null, $this->request);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage PromiseAdapter should be an object instantiating "Overblog\GraphQLBundle\Executor\Promise\PromiseAdapterInterface" or "GraphQL\Executor\Promise\PromiseAdapter" with a "wait" method.
     */
    public function testInvalidExecutorAdapterPromise()
    {
        $this->executor->setPromiseAdapter(new ReactPromiseAdapter());
        $this->executor->execute(null, $this->request);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage At least one schema should be declare.
     */
    public function testGetSchemaNoSchemaFound()
    {
        (new RequestExecutor(new Executor(), $this->dispatcher))->getSchema('fake');
    }

    private function createExecutorExecuteMock($returnValue)
    {
        $mock = $this->getMockBuilder(Executor::class)
            ->setMethods(['execute'])
            ->getMock();

        $mock->method('execute')->will($this->returnValue($returnValue));

        return $mock;
    }
}
