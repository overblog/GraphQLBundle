<?php

namespace Overblog\GraphQLBundle\Tests\Config;

use Overblog\GraphQLBundle\Config\Processor\InheritanceProcessor;
use PHPUnit\Framework\TestCase;

class InheritanceProcessorTest extends TestCase
{
    private $fixtures = [
        'foo' => [InheritanceProcessor::INHERITS_KEY => ['bar', 'baz'], 'type' => 'object', 'config' => []],
        'bar' => [InheritanceProcessor::INHERITS_KEY => ['toto'], 'type' => 'object', 'config' => []],
        'baz' => ['type' => 'object', 'config' => []],
        'toto' => ['type' => 'interface', 'config' => []],
    ];

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Type "toto" inherits by "bar" not found.
     */
    public function testExtendsUnknownType()
    {
        $configs = $this->fixtures;
        unset($configs['toto']);

        InheritanceProcessor::process($configs);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Type circular inheritance detected (foo->bar->toto->foo).
     */
    public function testCircularExtendsType()
    {
        $configs = $this->fixtures;
        $configs['toto'][InheritanceProcessor::INHERITS_KEY] = ['foo'];

        InheritanceProcessor::process($configs);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Type "toto" can't inherits "bar" because "enum" is not allowed type (["object","interface"]).
     */
    public function testNotAllowedType()
    {
        $configs = $this->fixtures;
        $configs['toto']['type'] = 'enum';

        InheritanceProcessor::process($configs);
    }
}
