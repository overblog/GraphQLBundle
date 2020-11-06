<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Connection;

use Exception;
use InvalidArgumentException;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionBuilder;
use Overblog\GraphQLBundle\Relay\Connection\ConnectionInterface;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\FulfilledPromise;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use stdClass;
use function call_user_func;
use function func_get_args;
use function React\Promise\resolve;

class ConnectionBuilderFromPromisedTest extends AbstractConnectionBuilderTest
{
    public function testReturnsAllElementsWithoutFilters(): void
    {
        $promise = call_user_func([static::getBuilder(), 'connectionFromPromisedArray'], $this->promisedLetters(), []);
        $expected = $this->getExpectedConnection($this->letters, false, false);
        $this->assertEqualsFromPromised($expected, $promise);
    }

    public function testRespectsASmallerFirst(): void
    {
        $promise = call_user_func([static::getBuilder(), 'connectionFromPromisedArray'], $this->promisedLetters(), ['first' => 2]);
        $expected = $this->getExpectedConnection(['A', 'B'], false, true);
        $this->assertEqualsFromPromised($expected, $promise);
    }

    /**
     * @param mixed $invalidPromise
     * @dataProvider invalidPromiseDataProvider
     */
    public function testInvalidPromise($invalidPromise): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This is not a valid promise.');
        call_user_func([static::getBuilder(), 'connectionFromPromisedArray'], $invalidPromise, []);
    }

    public function invalidPromiseDataProvider(): array
    {
        return [
            [new stdClass()],
            ['fake'],
            [['fake']],
            [false],
            [true],
        ];
    }

    public function testRespectsASmallerFirstWhenSlicing(): void
    {
        $promise = call_user_func([static::getBuilder(), 'connectionFromPromisedArraySlice'],
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
     * @param mixed $invalidPromise
     * @dataProvider invalidPromiseDataProvider
     */
    public function testInvalidPromiseWhenSlicing($invalidPromise): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This is not a valid promise.');
        call_user_func([static::getBuilder(), 'connectionFromPromisedArraySlice'], $invalidPromise, [], []);
    }

    /**
     * @return ExtendedPromiseInterface|FulfilledPromise|Promise|PromiseInterface
     */
    private function promisedLetters(array $letters = null)
    {
        return resolve($letters ?: $this->letters);
    }

    private function assertEqualsFromPromised(ConnectionInterface $expected, FulfilledPromise $promise): void
    {
        $this->assertSameConnection($expected, self::await($promise));
    }

    /**
     * @return mixed
     *
     * @throws Exception
     */
    private static function await(FulfilledPromise $promise)
    {
        $resolvedValue = null;
        $rejectedReason = null;
        $promise->then(
            function ($value) use (&$resolvedValue): void {
                $resolvedValue = $value;
            },
            function ($reason) use (&$rejectedReason): void {
                $rejectedReason = $reason;
            }
        );

        if ($rejectedReason instanceof Exception) {
            throw $rejectedReason;
        }

        return $resolvedValue;
    }

    /**
     * @return ConnectionBuilder
     */
    public static function getBuilder()
    {
        return new ConnectionBuilder(...func_get_args());
    }
}
