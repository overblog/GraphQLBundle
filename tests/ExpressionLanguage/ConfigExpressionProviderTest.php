<?php

namespace Overblog\GraphQLBundle\Tests\Error;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Tests\DIContainerMockTrait;
use PHPUnit\Framework\TestCase;

class ConfigExpressionProviderTest extends TestCase
{
    use DIContainerMockTrait;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    public function setUp()
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $container = $this->getDIContainerMock();
        $this->expressionLanguage->setContainer($container);
    }

    public function testService()
    {
        $object = new \stdClass();
        $container = $this->getDIContainerMock(['toto' => $object]);
        $this->expressionLanguage->setContainer($container);
        $this->assertEquals($object, eval('return '.$this->expressionLanguage->compile('service("toto")').';'));
    }

    public function testParameter()
    {
        $container = $this->getDIContainerMock([], ['test' => 5]);
        $this->expressionLanguage->setContainer($container);
        $this->assertEquals(5, eval('return '.$this->expressionLanguage->compile('parameter("test")').';'));
    }

    public function testIsTypeOf()
    {
        $this->assertTrue(eval('$value = new \stdClass(); return '.$this->expressionLanguage->compile(sprintf('isTypeOf("%s")', 'stdClass'), ['value']).';'));
    }

    public function testNewObject()
    {
        $this->assertInstanceOf('stdClass', eval('return '.$this->expressionLanguage->compile(sprintf('newObject("%s")', 'stdClass')).';'));
    }

    public function testFromGlobalId()
    {
        $this->assertEquals(['type' => 'User', 'id' => 15], eval('return '.$this->expressionLanguage->compile('fromGlobalId("VXNlcjoxNQ==")').';'));
    }

    public function testGlobalId()
    {
        $this->assertEquals('VXNlcjoxNQ==', eval('return '.$this->expressionLanguage->compile('globalId(15, "User")').';'));
    }
}
