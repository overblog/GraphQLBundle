<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection;

use GraphQL\Executor\Promise\Promise;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Paginator;
use PHPUnit\Framework\TestCase;
use function array_slice;
use function base64_encode;
use function count;

class PaginatorTest extends TestCase
{
    protected array $data = ['A', 'B', 'C', 'D', 'E'];

    /**
     * Generates an alphabet array starting at 'A' + $offset, ending always at 'E'.
     */
    public function getData(int $offset = 0): array
    {
        return array_slice($this->data, $offset);
    }

    public function testGetData(): void
    {
        $this->assertSame($this->data, $this->getData());
        $this->assertSame(['C', 'D', 'E'], $this->getData(2));
        $this->assertSame(['E'], $this->getData(4));
        $this->assertSame([], $this->getData(5));
    }

    /**
     * Compares node values with an array of expected values.
     */
    protected function assertSameEdgeNodeValue(array $expected, ConnectionInterface $result): void
    {
        $this->assertCount(count($expected), $result->getEdges());
        foreach ($expected as $key => $value) {
            $this->assertSame($value, $result->getEdges()[$key]->getNode()); // @phpstan-ignore-line
        }
    }

    public function testForward(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->forward(new Argument(['first' => 4]));

        $this->assertCount(4, $result->getEdges());
        $this->assertSameEdgeNodeValue(['A', 'B', 'C', 'D'], $result);
        $this->assertTrue($result->getPageInfo()->getHasNextPage());
    }

    public function testForwardAfterInMiddle(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(2, $offset);
            $this->assertSame(3, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->forward(new Argument(['first' => 1, 'after' => base64_encode('arrayconnection:2')]));

        $this->assertCount(1, $result->getEdges());
        $this->assertSameEdgeNodeValue(['D'], $result);
        $this->assertTrue($result->getPageInfo()->getHasNextPage());
    }

    public function testForwardAfterAtTheEnd(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(2, $offset);
            $this->assertSame(4, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->forward(new Argument(['first' => 2, 'after' => base64_encode('arrayconnection:2')]));

        $this->assertCount(2, $result->getEdges());
        $this->assertSameEdgeNodeValue(['D', 'E'], $result);
        $this->assertFalse($result->getPageInfo()->getHasNextPage());
    }

    public function testForwardAfterLast(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(4, $offset);
            $this->assertSame(7, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->forward(new Argument(['first' => 5, 'after' => base64_encode('arrayconnection:4')]));

        $this->assertCount(0, $result->getEdges());
        $this->assertSameEdgeNodeValue([], $result);
        $this->assertFalse($result->getPageInfo()->getHasNextPage());
    }

    public function testForwardAfterWithUnvalidCursorAndSlice(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->forward(new Argument(['first' => 4, 'after' => base64_encode('badcursor:aze')]));

        $this->assertCount(4, $result->getEdges());
        $this->assertSameEdgeNodeValue(['A', 'B', 'C', 'D'], $result);
        $this->assertTrue($result->getPageInfo()->getHasNextPage());
        $this->assertFalse($result->getPageInfo()->getHasPreviousPage());
    }

    public function testBackward(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(2, $offset);
            $this->assertSame(3, $limit);

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->backward(new Argument(['last' => 3]), 5);

        $this->assertCount(3, $result->getEdges());
        $this->assertSameEdgeNodeValue(['C', 'D', 'E'], $result);
        $this->assertTrue($result->getPageInfo()->getHasPreviousPage());
        $this->assertFalse($result->getPageInfo()->getHasNextPage());
    }

    public function testBackwardWithLimitEqualsToTotal(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit);

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->backward(new Argument(['last' => 5]), 5);

        $this->assertCount(5, $result->getEdges());
        $this->assertSameEdgeNodeValue($this->data, $result);
        $this->assertFalse($result->getPageInfo()->getHasPreviousPage());
        $this->assertFalse($result->getPageInfo()->getHasNextPage());
    }

    public function testBackwardBeforeLast(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(4, $limit);

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->backward(new Argument(['last' => 4, 'before' => base64_encode('arrayconnection:4')]), 5);

        $this->assertCount(4, $result->getEdges());
        $this->assertSameEdgeNodeValue(['A', 'B', 'C', 'D'], $result);
        $this->assertFalse($result->getPageInfo()->getHasPreviousPage());
    }

    public function testBackwardPartialBeforeInMiddle(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(1, $offset);
            $this->assertSame(2, $limit);

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->backward(new Argument(['last' => 2, 'before' => base64_encode('arrayconnection:3')]), 5);

        $this->assertCount(2, $result->getEdges());
        $this->assertSameEdgeNodeValue(['B', 'C'], $result);
        $this->assertTrue($result->getPageInfo()->getHasPreviousPage());
    }

    public function testAutoBackward(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(1, $offset);
            $this->assertSame(4, $limit);

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->auto(new Argument(['last' => 4]), 5);

        $this->assertCount(4, $result->getEdges());
        $this->assertSameEdgeNodeValue(['B', 'C', 'D', 'E'], $result);
        $this->assertTrue($result->getPageInfo()->getHasPreviousPage());
        $this->assertFalse($result->getPageInfo()->getHasNextPage());
    }

    public function testAutoForward(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit); // Includes the extra element to check if next page is available

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->auto(new Argument(['first' => 4]), 5);

        $this->assertCount(4, $result->getEdges());
        $this->assertSameEdgeNodeValue(['A', 'B', 'C', 'D'], $result);
        $this->assertTrue($result->getPageInfo()->getHasNextPage());
    }

    public function testAutoBackwardWithCallable(): void
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(1, $offset);
            $this->assertSame(4, $limit);

            return $this->getData($offset);
        });

        $countCalled = false;

        /** @var Connection $result */
        $result = $paginator->auto(new Argument(['last' => 4]), function () use (&$countCalled) {
            $countCalled = true;

            return 5;
        });

        $this->assertTrue($countCalled);
        $this->assertCount(4, $result->getEdges());
        $this->assertSameEdgeNodeValue(['B', 'C', 'D', 'E'], $result);
        $this->assertTrue($result->getPageInfo()->getHasPreviousPage());
    }

    public function testTotalCallableWithArguments(): void
    {
        $paginatorBackend = new PaginatorBackend();

        $callable = [
            $paginatorBackend,
            'count',
        ];

        $this->assertSame(5, $callable($this->data));

        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(1, $offset);
            $this->assertSame(4, $limit);

            return $this->getData($offset);
        });

        /** @var Connection $result */
        $result = $paginator->auto(
            new Argument(['last' => 4]),
            $callable,
            ['array' => $this->data]
        );

        $this->assertSame(count($this->data), $result->getTotalCount());

        $this->assertCount(4, $result->getEdges());
        $this->assertSameEdgeNodeValue(['B', 'C', 'D', 'E'], $result);
        $this->assertTrue($result->getPageInfo()->getHasPreviousPage());
    }

    public function testPromiseMode(): void
    {
        $promise = $this->getMockBuilder(Promise::class)
            ->disableOriginalConstructor()
            ->setMethods(['then'])
            ->getMock();

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
