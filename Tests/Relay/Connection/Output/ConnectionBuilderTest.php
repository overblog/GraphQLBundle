<?php

namespace Tests\Overblog\GraphQLBundle\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;

/**
 * Class ConnectionBuilderTest
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/connection/__tests__/arrayconnection.js
 */
class ConnectionBuilderTest extends \PHPUnit_Framework_TestCase
{
    private $letters = ['A', 'B', 'C', 'D', 'E'];

    public function testBasicSlicing()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters);

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
                new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjA=', 'YXJyYXljb25uZWN0aW9uOjQ=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsASmallerFirst()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['first' => 2]);

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjA=', 'YXJyYXljb25uZWN0aW9uOjE=', false, true)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsAnOverlyLargeFirst()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['first' => 10]);

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
                new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjA=', 'YXJyYXljb25uZWN0aW9uOjQ=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsASmallerLast()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['last' => 2]);

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
                new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjM=', 'YXJyYXljb25uZWN0aW9uOjQ=', true, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsAnOverlyLargeLast()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['last' => 10]);

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
                new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjA=', 'YXJyYXljb25uZWN0aW9uOjQ=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfter()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjE=']);

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjI=', 'YXJyYXljb25uZWN0aW9uOjM=', false, true)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfterWithLongFirst()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['first' => 10, 'after' => 'YXJyYXljb25uZWN0aW9uOjE=']);

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
                new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjI=', 'YXJyYXljb25uZWN0aW9uOjQ=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndBefore()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['last' => 2, 'before' => 'YXJyYXljb25uZWN0aW9uOjM=']);

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjE=', 'YXJyYXljb25uZWN0aW9uOjI=', true, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndBeforeWithLongLast()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['last' => 10, 'before' => 'YXJyYXljb25uZWN0aW9uOjM=']);

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjA=', 'YXJyYXljb25uZWN0aW9uOjI=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfterAndBeforeTooFew()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjE=', 'YXJyYXljb25uZWN0aW9uOjI=', false, true)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfterAndBeforeTooMany()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => 4, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjE=', 'YXJyYXljb25uZWN0aW9uOjM=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfterAndBeforeExactlyRight()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['first' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjE=', 'YXJyYXljb25uZWN0aW9uOjM=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndAfterAndBeforeTooFew()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['last' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjI=', 'YXJyYXljb25uZWN0aW9uOjM=', true, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndAfterAndBeforeTooMany()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['last' => 4, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjE=', 'YXJyYXljb25uZWN0aW9uOjM=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndAfterAndBeforeExactlyRight()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['last' => 3, 'after' => 'YXJyYXljb25uZWN0aW9uOjA=', 'before' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjE=', 'YXJyYXljb25uZWN0aW9uOjM=', false, false)
        );

        $this->assertEquals($expected, $actual);
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

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
                new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjA=', 'YXJyYXljb25uZWN0aW9uOjQ=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsAllElementsIfCursorsAreOnTheOutside()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['before' => 'YXJyYXljb25uZWN0aW9uOjYK', 'after' => 'YXJyYXljb25uZWN0aW9uOi0xCg==']
        );

        $expected = new Connection(
            [
                new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
                new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
                new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
                new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
                new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
            ],
            new PageInfo('YXJyYXljb25uZWN0aW9uOjA=', 'YXJyYXljb25uZWN0aW9uOjQ=', false, false)
        );

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsNoElementsIfCursorsCross()
    {
        $actual = ConnectionBuilder::connectionFromArray(
            $this->letters,
            ['before' => 'YXJyYXljb25uZWN0aW9uOjI=', 'after' => 'YXJyYXljb25uZWN0aW9uOjQ=']
        );

        $expected = new Connection(
            [
            ],
            new PageInfo(null, null, false, false)
        );

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
