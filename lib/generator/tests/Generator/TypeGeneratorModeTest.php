<?php

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests\Generator;

use Overblog\GraphQLGenerator\Generator\TypeGenerator;
use Overblog\GraphQLGenerator\Tests\TestCase;

class TypeGeneratorModeTest extends TestCase
{
    /** @var string */
    private $dir;

    /** @var TypeGenerator|\PHPUnit_Framework_MockObject_MockObject */
    private $typeGenerator;

    private static $configs = [
        'Query' => [
            'type' => 'object',
            'config' => [
                'fields' => [
                    'sayHello' => ['type' => 'String!'],
                ],
            ],
        ]
    ];

    public function setUp()
    {
        $this->dir = sys_get_temp_dir() . '/mcgweb-graphql-generator-modes';
        $this->typeGenerator = $this->getMockBuilder(TypeGenerator::class)
            ->setMethods(['processPlaceHoldersReplacements', 'processTemplatePlaceHoldersReplacements'])
            ->getMock()
        ;
    }

    public function testDryRunMode()
    {
        $this->typeGenerator->expects($this->once())->method('processPlaceHoldersReplacements');
        $this->typeGenerator->expects($this->once())->method('processTemplatePlaceHoldersReplacements');
        $this->assertGenerateClassesMode(TypeGenerator::MODE_DRY_RUN);
    }

    public function testMappingOnlyMode()
    {
        $this->typeGenerator->expects($this->never())->method('processPlaceHoldersReplacements');
        $this->typeGenerator->expects($this->never())->method('processTemplatePlaceHoldersReplacements');
        $this->assertGenerateClassesMode(TypeGenerator::MODE_MAPPING_ONLY);
    }

    private function assertGenerateClassesMode($mode)
    {
        $classes = $this->typeGenerator->generateClasses(self::$configs, $this->dir, $mode);
        $file = $this->dir.'/QueryType.php';
        $this->assertEquals(['Overblog\CG\GraphQLGenerator\__Schema__\QueryType' => $this->dir.'/QueryType.php'], $classes);
        if (method_exists($this, 'assertDirectoryNotExists')) {
            $this->assertDirectoryNotExists($this->dir);
        } else { // for phpunit 4
            $this->assertFalse(file_exists($this->dir));
            $this->assertFalse(is_dir($this->dir));
        }
        if (method_exists($this, 'assertFileNotExists')) {
            $this->assertFileNotExists($file);
        } else { // for phpunit 4
            $this->assertFalse(file_exists($file));
            $this->assertFalse(is_file($file));
        }
    }
}
