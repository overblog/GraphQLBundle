<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Relay\Connection;

use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Relay\Connection\Paginator;

class PaginatorTest extends \PHPUnit_Framework_TestCase
{
    public function testForward()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(6, $limit); // Includes the extra element to check if next page is available

            return array_fill(0, 6, 'item');
        });

        $this->assertCount(5, $paginator->forward(new Argument(['first' => 5]))->edges);
    }

    public function testForwardAfter()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(5, $offset);
            $this->assertSame(6, $limit); // Includes the extra element to check if next page is available

            return array_fill(0, 6, 'item');
        });

        $this->assertCount(5, $paginator->forward(new Argument(['first' => 5, 'after' => base64_encode('arrayconnection:5')]))->edges);
    }

    public function testBackward()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(5, $offset);
            $this->assertSame(5, $limit);

            return array_fill(0, 5, 'item');
        });

        $this->assertCount(5, $paginator->backward(new Argument(['last' => 5]), 10)->edges);
    }

    public function testBackwardBefore()
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(5, $limit);

            return array_fill(0, 5, 'item');
        });

        $this->assertCount(5, $paginator->backward(new Argument(['last' => 5, 'before' => base64_encode('arrayconnection:5')]), 10)->edges);
    }

    public function testAuto()
    {
        // Backward
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(5, $offset);
            $this->assertSame(5, $limit);

            return array_fill(0, 5, 'item');
        });

        $this->assertCount(5, $paginator->auto(new Argument(['last' => 5]), 10)->edges);

        // Forward
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(0, $offset);
            $this->assertSame(6, $limit); // Includes the extra element to check if next page is available

            return array_fill(0, 5, 'item');
        });

        $this->assertCount(5, $paginator->auto(new Argument(['first' => 5]), 10)->edges);

        // Backward + callable
        $paginator = new Paginator(function ($offset, $limit) {
            $this->assertSame(5, $offset);
            $this->assertSame(5, $limit);

            return array_fill(0, 5, 'item');
        });

        $countCalled = false;
        $result = $paginator->auto(new Argument(['last' => 5]), function () use (&$countCalled) {
            $countCalled = true;

            return 10;
        });

        $this->assertTrue($countCalled);
        $this->assertCount(5, $result->edges);
    }
}
