<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Processor;

use InvalidArgumentException;
use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
use PHPUnit\Framework\TestCase;

class InheritanceProcessorTest extends TestCase
{
    private array $fixtures = [
        'foo' => [InheritanceProcessor::INHERITS_KEY => ['bar', 'baz'], 'type' => 'object', 'config' => []],
        'bar' => [InheritanceProcessor::INHERITS_KEY => ['toto'], 'type' => 'object', 'config' => []],
        'baz' => ['type' => 'object', 'config' => []],
        'toto' => ['type' => 'interface', 'config' => []],
        'tata' => ['type' => 'interface', InheritanceProcessor::HEIRS_KEY => ['foo'], 'config' => []],
    ];

    public function testExtendsUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "toto" inherited by "bar" not found.');
        $configs = $this->fixtures;
        unset($configs['toto']);

        InheritanceProcessor::process($configs);
    }

    public function testHeirsUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "foo" child of "tata" not found.');
        $configs = $this->fixtures;
        unset($configs['foo']);

        InheritanceProcessor::process($configs);
    }

    public function testCircularExtendsType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type circular inheritance detected (foo->bar->toto->foo).');
        $configs = $this->fixtures;
        $configs['toto'][InheritanceProcessor::INHERITS_KEY] = ['foo'];

        InheritanceProcessor::process($configs);
    }

    public function testNotAllowedType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "bar" can\'t inherit "toto" because its type ("enum") is not allowed type (["object","interface"]).');
        $configs = $this->fixtures;
        $configs['toto']['type'] = 'enum';

        InheritanceProcessor::process($configs);
    }
}
