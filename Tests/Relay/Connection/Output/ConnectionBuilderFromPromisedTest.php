<?php

namespace Overblog\GraphQLBundle\Tests\Relay\Connection\Output;

use Overblog\GraphQLBundle\Relay\Connection\Output\ConnectionBuilder;
use React\Promise\FulfilledPromise;

class ConnectionBuilderFromPromisedTest extends AbstractConnectionBuilderTest
{
    public function testReturnsAllElementsWithoutFilters()
    {
        $promise = ConnectionBuilder::connectionFromPromisedArray($this->promisedLetters(), []);
        $expected = $this->getExpectedConnection($this->letters, false, false);
        $this->assertEqualsFromPromised($expected, $promise);
    }

    public function testRespectsASmallerFirst()
    {
        $promise = ConnectionBuilder::connectionFromPromisedArray($this->promisedLetters(), ['first' => 2]);
        $expected = $this->getExpectedConnection(['A', 'B'], false, true);
        $this->assertEqualsFromPromised($expected, $promise);
    }

    /**
     * @param $invalidPromise
     * @dataProvider invalidPromiseDataProvider
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage This is not a valid promise.
     */
    public function testInvalidPromise($invalidPromise)
    {
        ConnectionBuilder::connectionFromPromisedArray($invalidPromise, []);
    }

    public function invalidPromiseDataProvider()
    {
        return [
            [new \stdClass()],
            ['fake'],
            [['fake']],
            [false],
            [true],
        ];
    }

    public function testRespectsASmallerFirstWhenSlicing()
    {
        $promise = ConnectionBuilder::connectionFromPromisedArraySlice(
            $this->promisedLetters(['A', 'B', 'C']),
            ['first' => 2],
            [
                'sliceStart' => 0,
                'arrayLength' => 5,
            ]
        );
        $expected = $this->getExpectedConnection(['A', 'B'], false, true);
        $this->assertEqualsFromPromised($expected, $promise);
    }

    /**
     * @param $invalidPromise
     * @dataProvider invalidPromiseDataProvider
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage This is not a valid promise.
     */
    public function testInvalidPromiseWhenSlicing($invalidPromise)
    {
        ConnectionBuilder::connectionFromPromisedArraySlice($invalidPromise, [], []);
    }

    private function promisedLetters(array $letters = null)
    {
        return \React\Promise\resolve($letters ?: $this->letters);
    }

    private function assertEqualsFromPromised($expected, FulfilledPromise $promise)
    {
        $this->assertEquals($expected, self::await($promise));
    }

    private static function await(FulfilledPromise $promise)
    {
        $resolvedValue = null;
        $rejectedReason = null;
        $promise->then(
            function ($value) use (&$resolvedValue) {
                $resolvedValue = $value;
            },
            function ($reason) use (&$rejectedReason) {
                $rejectedReason = $reason;
            }
        );

        if ($rejectedReason instanceof \Exception) {
            throw $rejectedReason;
        }

        return $resolvedValue;
    }
}
