<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\Output\Connection;
use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;
use Overblog\GraphQLBundle\Relay\Connection\Output\Edge;
use Overblog\GraphQLBundle\Relay\Connection\Output\PageInfo;

/**
 * Class ConnectionBuilderTest.
 *
 * @see https://github.com/graphql/graphql-relay-js/blob/master/src/connection/__tests__/arrayconnection.js
 */
class ConnectionBuilderTest extends \PHPUnit_Framework_TestCase
{
    private $letters = ['A', 'B', 'C', 'D', 'E'];

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
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['first' => 2, 'after' => 'YXJyYXljb25uZWN0aW9uOjE=']);

        $expected = $this->getExpectedConnection(['C', 'D'], false, true);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsFirstAndAfterWithLongFirst()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['first' => 10, 'after' => 'YXJyYXljb25uZWN0aW9uOjE=']);

        $expected = $this->getExpectedConnection(['C', 'D', 'E'], false, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndBefore()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['last' => 2, 'before' => 'YXJyYXljb25uZWN0aW9uOjM=']);

        $expected = $this->getExpectedConnection(['B', 'C'], true, false);

        $this->assertEquals($expected, $actual);
    }

    public function testRespectsLastAndBeforeWithLongLast()
    {
        $actual = ConnectionBuilder::connectionFromArray($this->letters, ['last' => 10, 'before' => 'YXJyYXljb25uZWN0aW9uOjM=']);

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

    private function getExpectedConnection(array $wantedEdges, $hasPreviousPage, $hasNextPage)
    {
        $edges = [
            'A' => new Edge('YXJyYXljb25uZWN0aW9uOjA=', 'A'),
            'B' => new Edge('YXJyYXljb25uZWN0aW9uOjE=', 'B'),
            'C' => new Edge('YXJyYXljb25uZWN0aW9uOjI=', 'C'),
            'D' => new Edge('YXJyYXljb25uZWN0aW9uOjM=', 'D'),
            'E' => new Edge('YXJyYXljb25uZWN0aW9uOjQ=', 'E'),
        ];

        $expectedEdges = array_values(array_intersect_key($edges, array_flip($wantedEdges)));

        return new Connection(
            $expectedEdges,
            new PageInfo(
                isset($expectedEdges[0]) ? $expectedEdges[0]->cursor : null,
                end($expectedEdges) ? end($expectedEdges)->cursor : null,
                $hasPreviousPage,
                $hasNextPage
            )
        );
    }
}
