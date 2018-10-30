<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection;

use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder;
use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;

/**
 * Class ConnectionBuilderTest.
 *
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/connection/__tests__/arrayconnection.js
 */
class ConnectionBuilderTest extends AbstractConnectionBuilderTest
{
    public function testBasicSlicing(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray($this->letters);

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsASmallerFirst(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray($this->letters, ['first' => 2]);

        $expected = $this->getExpectedConnection(['A', 'B'], false, true);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsAnOverlyLargeFirst(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray($this->letters, ['first' => 10]);

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsASmallerLast(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray($this->letters, ['last' => 2]);

        $expected = $this->getExpectedConnection(['D', 'E'], true, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsAnOverlyLargeLast(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray($this->letters, ['last' => 10]);

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsFirstAndAfter(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjE=']
        );

        $expected = $this->getExpectedConnection(['C', 'D'], false, true);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsFirstAndAfterWithLongFirst(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['first' => 10, 'after' => 'YXJyYXljb25uZWN0aW9uOjE=']
        );

        $expected = $this->getExpectedConnection(['C', 'D', 'E'], false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsLastAndBefore(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['last' => 2, 'before' => 'YXJyYXljb25uZWN0aW9uOjM=']
        );

        $expected = $this->getExpectedConnection(['B', 'C'], true, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsLastAndBeforeWithLongLast(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['last' => 10, 'before' => 'YXJyYXljb25uZWN0aW9uOjM=']
        );

        $expected = $this->getExpectedConnection(['A', 'B', 'C'], false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsFirstAndAfterAndBeforeTooFew(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C'], false, true);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsFirstAndAfterAndBeforeTooMany(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['first' => 4, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C', 'D'], false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsFirstAndAfterAndBeforeExactlyRight(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['first' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C', 'D'], false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsLastAndAfterAndBeforeTooFew(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['last' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['C', 'D'], true, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsLastAndAfterAndBeforeTooMany(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['last' => 4, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C', 'D'], false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testRespectsLastAndAfterAndBeforeExactlyRight(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['last' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C', 'D'], false, false);

        $this->assertSameConnection($expected, $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument "first" must be a non-negative integer
     */
    public function testThrowsAnErrorIfFirstLessThan0(): void
    {
        (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['first' => -1]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument "last" must be a non-negative integer
     */
    public function testThrowsAnErrorIfLastLessThan0(): void
    {
        (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['last' => -1]
        );
    }

    public function testReturnsNoElementsIfFirstIs0(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['first' => 0]
        );

        $expected = new Connection(
            [],
            new PageInfo(null, null, false, true)
        );

        $this->assertSameConnection($expected, $actual);
    }

    public function testReturnsAllElementsIfCursorsAreInvalid(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['before' => 'invalid', 'after' => 'invalid']
        );

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testReturnsAllElementsIfCursorsAreOnTheOutside(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['before' => 'YXJyYXljb25uZWN0aW9uOjYK', 'after' => 'YXJyYXljb25uZWN0aW9uOi0xCg==']
        );

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertSameConnection($expected, $actual);
    }

    public function testReturnsNoElementsIfCursorsCross(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArray(
            $this->letters,
            ['before' => 'YXJyYXljb25uZWN0aW9uOjI=', 'after' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection([], false, false);

        $this->assertSameConnection($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with a just-right array slice.
     */
    public function testWorksWithAJustRightArraySlice(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArraySlice(
            \array_slice($this->letters, 1, 2), // equals to letters.slice(1,3) in JS
            ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA='],
            ['sliceStart' => 1, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['B', 'C'], false, true);

        $this->assertSameConnection($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an oversized array slice ("left" side).
     */
    public function testWorksWithAnOversizedArraySliceLeftSide(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArraySlice(
            \array_slice($this->letters, 0, 3), // equals to letters.slice(0,3) in JS
            ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA='],
            ['sliceStart' => 0, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['B', 'C'], false, true);

        $this->assertSameConnection($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an oversized array slice ("right" side).
     */
    public function testWorksWithAnOversizedArraySliceRightSide(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArraySlice(
            \array_slice($this->letters, 2, 2), // equals to letters.slice(2,4) in JS
            ['first' => 1, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 2, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['C'], false, true);

        $this->assertSameConnection($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an oversized array slice (both sides).
     */
    public function testWorksWithAnOversizedArraySliceBothSides(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArraySlice(
            \array_slice($this->letters, 1, 3), // equals to letters.slice(1,4) in JS
            ['first' => 1, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 1, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['C'], false, true);

        $this->assertSameConnection($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an undersized array slice ("left" side).
     */
    public function testWorksWithAnUndersizedArraySliceLeftSide(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArraySlice(
            \array_slice($this->letters, 3, 2), // equals to letters.slice(3,5) in JS
            ['first' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 3, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['D', 'E'], false, false);

        $this->assertSameConnection($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an undersized array slice ("right" side).
     */
    public function testWorksWithAnUndersizedArraySliceRightSide(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArraySlice(
            \array_slice($this->letters, 2, 2), // equals to letters.slice(2,4) in JS
            ['first' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 2, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['C', 'D'], false, true);

        $this->assertSameConnection($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an undersized array slice (both sides).
     */
    public function worksWithAnUndersizedArraySliceBothSides(): void
    {
        $actual = (new ConnectionBuilder())->connectionFromArraySlice(
            \array_slice($this->letters, 3, 1), // equals to letters.slice(3,4) in JS
            ['first' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 3, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['D'], false, true);

        $this->assertSameConnection($expected, $actual);
    }

    public function testReturnsAnEdgesCursorGivenAnArrayAndAMemberObject(): void
    {
        $letterCursor = (new ConnectionBuilder())->cursorForObjectInConnection($this->letters, 'B');

        $this->assertSame('YXJyYXljb25uZWN0aW9uOjE=', $letterCursor);
    }

    public function testReturnsAnEdgesCursorGivenAnArrayAndANonMemberObject(): void
    {
        $letterCursor = (new ConnectionBuilder())->cursorForObjectInConnection($this->letters, 'F');

        $this->assertNull($letterCursor);
    }

    public function testConnectionCallback(): void
    {
        $connectionBuilder = new ConnectionBuilder(function ($edges, $pageInfo) {
            $connection = new fixtures\CustomConnection($edges, $pageInfo);
            $connection->averageAge = 10;

            return $connection;
        });

        $actual = $connectionBuilder->connectionFromArray($this->letters);
        $this->assertInstanceOf(fixtures\CustomConnection::class, $actual);
        $this->assertEquals($actual->averageAge, 10);
    }

    public function testEdgeCallback(): void
    {
        $connectionBuilder = new ConnectionBuilder(null, function ($cursor, $value, $index) {
            $edge = new fixtures\CustomEdge($cursor, $value);
            $edge->customProperty = 'edge'.$index;

            return $edge;
        });

        $actualEdges = $connectionBuilder->connectionFromArray($this->letters, ['first' => 2])->getEdges();
        $this->assertInstanceOf(fixtures\CustomEdge::class, $actualEdges[0]);
        $this->assertInstanceOf(fixtures\CustomEdge::class, $actualEdges[1]);

        $this->assertEquals($actualEdges[0]->getCursor(), 'YXJyYXljb25uZWN0aW9uOjA=');
        $this->assertEquals($actualEdges[1]->getCursor(), 'YXJyYXljb25uZWN0aW9uOjE=');

        $this->assertEquals($actualEdges[0]->customProperty, 'edge0');
        $this->assertEquals($actualEdges[1]->customProperty, 'edge1');
    }
}
