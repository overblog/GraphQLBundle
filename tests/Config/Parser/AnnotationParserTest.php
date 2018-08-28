<?php

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;

class AnnotationParserTest extends TestCase
{
    public function testParse()
    {
        $expected = [
            'RootQuery' => [
                'type' => 'object',
                'config' => [
                    'fields' => [
                        'hero' => [
                            'type' => 'Character',
                            'resolve' => "@=resolver('App\\\\MyResolver::getHero')",
                        ],
                        'droid' => [
                            'type' => 'Droid',
                            'resolve' => "@=resolver('App\\\\MyResolver::getDroid')",
                        ],
                    ],
                    'description' => 'RootQuery type',
                ],
            ],
        ];
        $fileName = __DIR__.'/fixtures/Entity/GraphQL/RootQuery.php';

        $this->assertContainerAddFileToResources($fileName);
        $config = AnnotationParser::parse(new \SplFileInfo($fileName), $this->containerBuilder);
        $this->assertEquals($expected, self::cleanConfig($config));
    }

}
