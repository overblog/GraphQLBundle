<?php

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;

/**
 * Class ConnectionBuilderTest.
 *
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/connection/__tests__/arrayconnection.js
 */
class ConnectionBuilderTest extends AbstractConnectionBuilderTest
{
    public function testBasicSlicing()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters);

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsASmallerFirst()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['first' => 2]);

        $expected = $this->getExpectedConnection(['A', 'B'], false, true);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsAnOverlyLargeFirst()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['first' => 10]);

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsASmallerLast()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['last' => 2]);

        $expected = $this->getExpectedConnection(['D', 'E'], true, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsAnOverlyLargeLast()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['last' => 10]);

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfter()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjE=']
        );

        $expected = $this->getExpectedConnection(['C', 'D'], false, true);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfterWithLongFirst()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => 10, 'after' => 'YXJyYXljb25uZWN0aW9uOjE=']
        );

        $expected = $this->getExpectedConnection(['C', 'D', 'E'], false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndBefore()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['last' => 2, 'before' => 'YXJyYXljb25uZWN0aW9uOjM=']
        );

        $expected = $this->getExpectedConnection(['B', 'C'], true, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndBeforeWithLongLast()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['last' => 10, 'before' => 'YXJyYXljb25uZWN0aW9uOjM=']
        );

        $expected = $this->getExpectedConnection(['A', 'B', 'C'], false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfterAndBeforeTooFew()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C'], false, true);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfterAndBeforeTooMany()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => 4, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C', 'D'], false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfterAndBeforeExactlyRight()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C', 'D'], false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndAfterAndBeforeTooFew()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['last' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['C', 'D'], true, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndAfterAndBeforeTooMany()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['last' => 4, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C', 'D'], false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndAfterAndBeforeExactlyRight()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['last' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection(['B', 'C', 'D'], false, false);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument "first" must be a non-negative integer
     */
    public function testThrowsAnErrorIfFirstLessThan0()
    {
        ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => -1]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Argument "last" must be a non-negative integer
     */
    public function testThrowsAnErrorIfLastLessThan0()
    {
        ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['last' => -1]
        );
    }

    public function testReturnsNoElementsIfFirstIs0()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => 0]
        );

        $expected = new Connection(
            [
            ],
            new PageInfo(null, null, false, true)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsAllElementsIfCursorsAreInvalid()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['before' => 'invalid', 'after' => 'invalid']
        );

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsAllElementsIfCursorsAreOnTheOutside()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['before' => 'YXJyYXljb25uZWN0aW9uOjYK', 'after' => 'YXJyYXljb25uZWN0aW9uOi0xCg==']
        );

        $expected = $this->getExpectedConnection($this->letters, false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsNoElementsIfCursorsCross()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['before' => 'YXJyYXljb25uZWN0aW9uOjI=', 'after' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = $this->getExpectedConnection([], false, false);

        $this->assertEquals($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with a just-right array slice.
     */
    public function testWorksWithAJustRightArraySlice()
    {
        $actual = ConnectionBuilder::connectionFromArraySlice(
            array_slice($this->letters, 1, 2), // equals to letters.slice(1,3) in JS
            ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA='],
            ['sliceStart' => 1, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['B', 'C'], false, true);

        $this->assertEquals($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an oversized array slice ("left" side).
     */
    public function testWorksWithAnOversizedArraySliceLeftSide()
    {
        $actual = ConnectionBuilder::connectionFromArraySlice(
            array_slice($this->letters, 0, 3), // equals to letters.slice(0,3) in JS
            ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA='],
            ['sliceStart' => 0, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['B', 'C'], false, true);

        $this->assertEquals($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an oversized array slice ("right" side).
     */
    public function testWorksWithAnOversizedArraySliceRightSide()
    {
        $actual = ConnectionBuilder::connectionFromArraySlice(
            array_slice($this->letters, 2, 2), // equals to letters.slice(2,4) in JS
            ['first' => 1, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 2, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['C'], false, true);

        $this->assertEquals($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an oversized array slice (both sides).
     */
    public function testWorksWithAnOversizedArraySliceBothSides()
    {
        $actual = ConnectionBuilder::connectionFromArraySlice(
            array_slice($this->letters, 1, 3), // equals to letters.slice(1,4) in JS
            ['first' => 1, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 1, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['C'], false, true);

        $this->assertEquals($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an undersized array slice ("left" side).
     */
    public function testWorksWithAnUndersizedArraySliceLeftSide()
    {
        $actual = ConnectionBuilder::connectionFromArraySlice(
            array_slice($this->letters, 3, 2), // equals to letters.slice(3,5) in JS
            ['first' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 3, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['D', 'E'], false, false);

        $this->assertEquals($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an undersized array slice ("right" side).
     */
    public function testWorksWithAnUndersizedArraySliceRightSide()
    {
        $actual = ConnectionBuilder::connectionFromArraySlice(
            array_slice($this->letters, 2, 2), // equals to letters.slice(2,4) in JS
            ['first' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 2, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['C', 'D'], false, true);

        $this->assertEquals($expected, $actual);
    }

    /**
     * transcript of JS implementation test : works with an undersized array slice (both sides).
     */
    public function worksWithAnUndersizedArraySliceBothSides()
    {
        $actual = ConnectionBuilder::connectionFromArraySlice(
            array_slice($this->letters, 3, 1), // equals to letters.slice(3,4) in JS
            ['first' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjE='],
            ['sliceStart' => 3, 'arrayLength' => 5]
        );

        $expected = $this->getExpectedConnection(['D'], false, true);

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsAnEdgesCursorGivenAnArrayAndAMemberObject()
    {
        $letterCursor = ConnectionBuilder::cursorForObjectInConnection($this->letters, 'B');

        $this->assertEquals('YXJyYXljb25uZWN0aW9uOjE=', $letterCursor);
    }

    public function testReturnsAnEdgesCursorGivenAnArrayAndANonMemberObject()
    {
        $letterCursor = ConnectionBuilder::cursorForObjectInConnection($this->letters, 'F');

        $this->assertNull($letterCursor);
    }
}
