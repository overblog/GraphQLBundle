<?php

namespace Overblog\GraphQLBundle\Tests\Definition;

use Overblog\GraphQLBundle\Definition\Argument;
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    /** @var array */
    private $rawArgs;

    /** @var Argument */
    private $argument;

    public function setUp()
    {
        $this->rawArgs = ['toto' => 'tata'];

        $this->argument = new Argument($this->rawArgs);
    }

    public function testOffsetGet()
    {
        $this->assertEquals($this->argument['toto'], 'tata');
        $this->assertNull($this->argument['fake']);
    }

    public function testOffsetSet()
    {
        $this->argument['foo'] = 'bar';
        $this->assertEquals($this->argument['foo'], 'bar');
    }

    public function testOffsetExists()
    {
        unset($this->argument['toto']);
        $this->assertNull($this->argument['toto']);
    }

    public function testOffsetUnset()
    {
        $this->assertTrue(isset($this->argument['toto']));
    }

    public function testCount()
    {
        $this->assertCount(1, $this->argument);
    }

    public function testGetRawArgs()
    {
        $this->assertEquals($this->rawArgs, $this->argument->getRawArguments());
    }
}
