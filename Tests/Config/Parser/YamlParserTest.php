<?php

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Overblog\GraphQLBundle\Config\Parser\YamlParser;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class YamlParserTest extends TestCase
{
    /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject */
    private $containerBuilder;

    public function setUp()
    {
        $this->containerBuilder = $this->getMockBuilder(ContainerBuilder::class)->setMethods(['addResource'])->getMock();
    }

    public function testParseEmptyFile()
    {
        $fileName = __DIR__.'/fixtures/yml/empty.yml';

        $this->assertContainerAddFileToResources($fileName);

        $config = YamlParser::parse(new \SplFileInfo($fileName), $this->containerBuilder);
        $this->assertEquals([], $config);
    }

    public function testParseInvalidFile()
    {
        $fileName = __DIR__.'/fixtures/yml/invalid.yml';
        file_put_contents($fileName, iconv('UTF-8', 'ISO-8859-1', "not_utf-8: 'äöüß'"));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The file "%s" does not contain valid YAML.', $fileName));
        YamlParser::parse(new \SplFileInfo($fileName), $this->containerBuilder);
    }

    public function testParseConstant()
    {
        $expected = ['values' => ['constant' => Constant::CONSTANT]];
        $actual = YamlParser::parse(new \SplFileInfo(__DIR__.'/fixtures/yml/constant.yml'), $this->containerBuilder);
        $this->assertEquals($expected, $actual);
    }

    private function assertContainerAddFileToResources($fileName)
    {
        $this->containerBuilder->expects($this->once())
            ->method('addResource')
            ->with($fileName);
    }
}
