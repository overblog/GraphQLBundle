<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Definition;

use Overblog\GraphQLBundle\Definition\Argument;
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    /** @var array */
    private $rawArgs;

    /** @var Argument */
    private $argument;

    public function setUp(): void
    {
        $this->rawArgs = ['toto' => 'tata'];

        $this->argument = new Argument($this->rawArgs);
    }

    public function testOffsetGet(): void
    {
        $this->assertSame($this->argument['toto'], 'tata');
        $this->assertNull($this->argument['fake']);
    }

    public function testOffsetSet(): void
    {
        $this->argument['foo'] = 'bar';
        $this->assertSame($this->argument['foo'], 'bar');
    }

    public function testOffsetExists(): void
    {
        unset($this->argument['toto']);
        $this->assertNull($this->argument['toto']);
    }

    public function testOffsetUnset(): void
    {
        $this->assertTrue(isset($this->argument['toto']));
    }

    public function testCount(): void
    {
        $this->assertCount(1, $this->argument);
    }

    public function testGetRawArgs(): void
    {
        $this->assertSame($this->rawArgs, $this->argument->getArrayCopy());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation This "%s" method is deprecated since 0.12 and will be removed in 0.13. You should use "%s::getArrayCopy" instead.
     */
    public function testDeprecatedGetRawArgs(): void
    {
        $this->assertSame($this->rawArgs, $this->argument->getRawArguments());
    }
}
