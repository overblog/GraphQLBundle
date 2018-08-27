<?php

namespace Overblog\GraphQLBundle\Tests\Relay\Connection;

use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    protected $data = ['A', 'B', 'C', 'D', 'E'];

    /**
     * Generates an alphabet array starting at 'A' + $offset, ending always at 'E'.
     *
     * @param int $offset
     *
     * @return array
     */
    public function getData($offset = 0)
    {
        return array_slice($this->data, $offset);
    }

    public function testGetData()
    {
        $this->assertSame($this->data, $this->getData());
        $this->assertSame(['C', 'D', 'E'], $this->getData(2));
        $this->assertSame(['E'], $this->getData(4));
        $this->assertSame([], $this->getData(5));
    }

    /**
     * Compares node values with an array of expected values.
     *
     * @param $expected
     * @param $result
     */
    protected function assertSameEdgeNodeValue($expected, Connection $result)
    {
        $this->assertEquals(count($expected), count($result->edges));
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $result->edges[$key]->node);
        }
    }

    public function testForward()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        $result = $paginator->forward(new Argument(['first' => 4]));

        $this->assertCount(4, $result->edges);
        $this->assertSameEdgeNodeValue(['A', 'B', 'C', 'D'], $result);
        $this->assertTrue($result->pageInfo->hasNextPage);
    }

    public function testForwardAfterInMiddle()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(2, $offset);
            $this->assertSame(3, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        $result = $paginator->forward(new Argument(['first' => 1, 'after' => base64_encode('arrayconnection:2')]));

        $this->assertCount(1, $result->edges);
        $this->assertSameEdgeNodeValue(['D'], $result);
        $this->assertTrue($result->pageInfo->hasNextPage);
    }

    public function testForwardAfterAtTheEnd()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(2, $offset);
            $this->assertSame(4, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        $result = $paginator->forward(new Argument(['first' => 2, 'after' => base64_encode('arrayconnection:2')]));

        $this->assertCount(2, $result->edges);
        $this->assertSameEdgeNodeValue(['D', 'E'], $result);
        $this->assertFalse($result->pageInfo->hasNextPage);
    }

    public function testForwardAfterLast()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(4, $offset);
            $this->assertSame(7, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        $result = $paginator->forward(new Argument(['first' => 5, 'after' => base64_encode('arrayconnection:4')]));

        $this->assertCount(0, $result->edges);
        $this->assertSameEdgeNodeValue([], $result);
        $this->assertFalse($result->pageInfo->hasNextPage);
    }

    public function testForwardAfterWithUnvalidCursorAndSlice()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        $result = $paginator->forward(new Argument(['first' => 4, 'after' => base64_encode('badcursor:aze')]));

        $this->assertCount(4, $result->edges);
        $this->assertSameEdgeNodeValue(['A', 'B', 'C', 'D'], $result);
        $this->assertTrue($result->pageInfo->hasNextPage);
        $this->assertFalse($result->pageInfo->hasPreviousPage);
    }

    public function testBackward()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(2, $offset);
            $this->assertSame(3, $limit);

            return $this->getData($offset);
        });

        $result = $paginator->backward(new Argument(['last' => 3]), 5);

        $this->assertCount(3, $result->edges);
        $this->assertSameEdgeNodeValue(['C', 'D', 'E'], $result);
        $this->assertTrue($result->pageInfo->hasPreviousPage);
        $this->assertFalse($result->pageInfo->hasNextPage);
    }

    public function testBackwardWithLimitEqualsToTotal()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit);

            return $this->getData($offset);
        });

        $result = $paginator->backward(new Argument(['last' => 5]), 5);

        $this->assertCount(5, $result->edges);
        $this->assertSameEdgeNodeValue($this->data, $result);
        $this->assertFalse($result->pageInfo->hasPreviousPage);
        $this->assertFalse($result->pageInfo->hasNextPage);
    }

    public function testBackwardBeforeLast()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(4, $limit);

            return $this->getData($offset);
        });

        $result = $paginator->backward(new Argument(['last' => 4, 'before' => base64_encode('arrayconnection:4')]), 5);

        $this->assertCount(4, $result->edges);
        $this->assertSameEdgeNodeValue(['A', 'B', 'C', 'D'], $result);
        $this->assertFalse($result->pageInfo->hasPreviousPage);
    }

    public function testBackwardPartialBeforeInMiddle()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(1, $offset);
            $this->assertSame(2, $limit);

            return $this->getData($offset);
        });

        $result = $paginator->backward(new Argument(['last' => 2, 'before' => base64_encode('arrayconnection:3')]), 5);

        $this->assertCount(2, $result->edges);
        $this->assertSameEdgeNodeValue(['B', 'C'], $result);
        $this->assertTrue($result->pageInfo->hasPreviousPage);
    }

    public function testAutoBackward()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(1, $offset);
            $this->assertSame(4, $limit);

            return $this->getData($offset);
        });

        $result = $paginator->auto(new Argument(['last' => 4]), 5);

        $this->assertCount(4, $result->edges);
        $this->assertSameEdgeNodeValue(['B', 'C', 'D', 'E'], $result);
        $this->assertTrue($result->pageInfo->hasPreviousPage);
        $this->assertFalse($result->pageInfo->hasNextPage);
    }

    public function testAutoForward()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        $result = $paginator->auto(new Argument(['first' => 4]), 5);

        $this->assertCount(4, $result->edges);
        $this->assertSameEdgeNodeValue(['A', 'B', 'C', 'D'], $result);
        $this->assertTrue($result->pageInfo->hasNextPage);
    }

    public function testAutoBackwardWithCallable()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(1, $offset);
            $this->assertSame(4, $limit);

            return $this->getData($offset);
        });

        $countCalled = false;
        $result = $paginator->auto(new Argument(['last' => 4]), function () use (&$countCalled) {
            $countCalled = true;

            return 5;
        });

        $this->assertTrue($countCalled);
        $this->assertCount(4, $result->edges);
        $this->assertSameEdgeNodeValue(['B', 'C', 'D', 'E'], $result);
        $this->assertTrue($result->pageInfo->hasPreviousPage);
    }

    public function testTotalCallableWithArguments()
    {
        $paginatorBackend = new PaginatorBackend();

        $callable = [
            $paginatorBackend,
            'count',
        ];

        $this->assertSame(5, call_user_func_array($callable, ['array' => $this->data]));

        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(1, $offset);
            $this->assertSame(4, $limit);

            return $this->getData($offset);
        });

        $result = $paginator->auto(
            new Argument(['last' => 4]),
            $callable,
            ['array' => $this->data]
        );

        $this->assertSame(count($this->data), $result->totalCount);

        $this->assertCount(4, $result->edges);
        $this->assertSameEdgeNodeValue(['B', 'C', 'D', 'E'], $result);
        $this->assertTrue($result->pageInfo->hasPreviousPage);
    }

    public function testPromiseMode()
    {
        $promise = $this->getMockBuilder(Promise::class)
            ->disableOriginalConstructor()
            ->setMethods(['then'])
            ->getMock()
        ;

        $promise
            ->expects($this->exactly(2))
            ->method('then')
            ->willReturnSelf();

        $paginator = new Paginator(function ($offset, $limit) use ($promise) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit);

            return $promise;
        }, Paginator::MODE_PROMISE);

        $result = $paginator->auto(new Argument(['first' => 4]), 5);
    }
}
