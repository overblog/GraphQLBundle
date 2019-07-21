<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use Overblog\GraphQLBundle\Command\CompileCommand;
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

    public function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'generatorCommand']);

        $this->command = static::$kernel->getContainer()->get(CompileCommand::class);
        $this->typesMapping = static::$kernel->getContainer()->get('overblog_graphql.cache_compiler')
            ->compile(TypeGenerator::MODE_MAPPING_ONLY);
        $this->cacheDir = static::$kernel->getContainer()->get('overblog_graphql.cache_compiler')->getCacheDir();
        $this->commandTester = new CommandTester($this->command);
    }

    public function testFilesNotExistsBeforeGeneration(): void
    {
        foreach ($this->typesMapping as $class => $path) {
            $this->assertFileNotExists($path);
        }
    }

    public function testGeneration(): void
    {
        $this->commandTester->execute([]);
        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertSame($this->displayExpected(), $this->commandTester->getDisplay());
        foreach ($this->typesMapping as $class => $path) {
            $this->assertFileExists($path);
        }
    }

    public function testVerboseGeneration(): void
    {
        $this->commandTester->execute([], ['verbosity' => Output::VERBOSITY_VERBOSE]);
        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertRegExp(
            '@'.$this->displayExpected(true).'@',
            \preg_replace('@\.php\s*'.\PHP_EOL.'@', '.php'.\PHP_EOL, $this->commandTester->getDisplay())
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
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\QueryType              {{PATH}}/QueryType\.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\UserType               {{PATH}}/UserType\.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\friendConnectionType   {{PATH}}/friendConnectionType\.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\userConnectionType     {{PATH}}/userConnectionType\.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\PageInfoType           {{PATH}}/PageInfoType\.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\friendEdgeType         {{PATH}}/friendEdgeType\.php
  Overblog\\GraphQLBundle\\Connection\\__DEFINITIONS__\\userEdgeType           {{PATH}}/userEdgeType\.php
 \-[\-]+\s+\-[\-]+\s
OUTPUT;
            $display = \str_replace('{{PATH}}', \preg_quote($this->cacheDir), $display);
        }

        return \str_replace("\n", \PHP_EOL, $display);
    }
}
