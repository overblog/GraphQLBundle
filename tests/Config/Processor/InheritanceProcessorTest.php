<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Processor;

use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
use PHPUnit\Framework\TestCase;

class InheritanceProcessorTest extends TestCase
{
    private $fixtures = [
        'foo' => [InheritanceProcessor::INHERITS_KEY => ['bar', 'baz'], 'type' => 'object', 'config' => []],
        'bar' => [InheritanceProcessor::INHERITS_KEY => ['toto'], 'type' => 'object', 'config' => []],
        'baz' => ['type' => 'object', 'config' => []],
        'toto' => ['type' => 'interface', 'config' => []],
        'tata' => ['type' => 'interface', InheritanceProcessor::HEIRS_KEY => ['foo'], 'config' => []],
    ];

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Type "toto" inherited by "bar" not found.
     */
    public function testExtendsUnknownType(): void
    {
        $configs = $this->fixtures;
        unset($configs['toto']);

        InheritanceProcessor::process($configs);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Type "foo" child of "tata" not found.
     */
    public function testHeirsUnknownType(): void
    {
        $configs = $this->fixtures;
        unset($configs['foo']);

        InheritanceProcessor::process($configs);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Type circular inheritance detected (foo->bar->toto->foo).
     */
    public function testCircularExtendsType(): void
    {
        $configs = $this->fixtures;
        $configs['toto'][InheritanceProcessor::INHERITS_KEY] = ['foo'];

        InheritanceProcessor::process($configs);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Type "bar" can't inherit "toto" because its type ("enum") is not allowed type (["object","interface"]).
     */
    public function testNotAllowedType(): void
    {
        $configs = $this->fixtures;
        $configs['toto']['type'] = 'enum';

        InheritanceProcessor::process($configs);
    }
}
