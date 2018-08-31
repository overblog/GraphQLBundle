<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Tester\CommandTester;

class CompileCommandTest extends TestCase
{
    /** @var Command */
    private $command;

    /** @var CommandTester */
    private $commandTester;

    /** @var array */
    private $typesMapping;

    /** @var string */
    private $cacheDir;

    public function setUp()
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'generatorCommand']);

        $this->command = static::$kernel->getContainer()->get('overblog_graphql.command.compile');
        $this->typesMapping = static::$kernel->getContainer()->get('overblog_graphql.cache_compiler')
            ->compile(TypeGenerator::MODE_MAPPING_ONLY);
        $this->cacheDir = static::$kernel->getContainer()->get('overblog_graphql.cache_compiler')->getCacheDir();
        $this->commandTester = new CommandTester($this->command);
    }

    public function testFilesNotExistsBeforeGeneration()
    {
        foreach ($this->typesMapping as $class => $path) {
            $this->assertFileNotExists($path);
        }
    }

    public function testGeneration()
    {
        $this->commandTester->execute([]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertEquals($this->displayExpected(), $this->commandTester->getDisplay());
        foreach ($this->typesMapping as $class => $path) {
            $this->assertFileExists($path);
        }
    }

    public function testVerboseGeneration()
    {
        $this->commandTester->execute([], ['verbosity' => Output::VERBOSITY_VERBOSE]);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertRegExp(
            '@'.$this->displayExpected(true).'@',
            \preg_replace('@\.php[^\n]*\n@', ".php\n", $this->commandTester->getDisplay())
        );
    }

    private function displayExpected($isVerbose = false)
    {
        $display = <<<'OUTPUT'
Types compilation starts
Types compilation ends successfully

OUTPUT;

        if ($isVerbose) {
            $display .= <<<'OUTPUT'

Summary
=======

 \-[\-]+\s+\-[\-]+\s
  class\s+path\s*
 \-[\-]+\s+\-[\-]+\s
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\QueryType              {{PATH}}/QueryType.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\UserType               {{PATH}}/UserType.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\friendConnectionType   {{PATH}}/friendConnectionType.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\userConnectionType     {{PATH}}/userConnectionType.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\PageInfoType           {{PATH}}/PageInfoType.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\friendEdgeType         {{PATH}}/friendEdgeType.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\userEdgeType           {{PATH}}/userEdgeType.php
 \-[\-]+\s+\-[\-]+\s


OUTPUT;
            $display = \str_replace('{{PATH}}', $this->cacheDir, $display);
        }

        return $display;
    }
}
