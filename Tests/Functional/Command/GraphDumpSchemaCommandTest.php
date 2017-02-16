<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\Command;

use Overblog\GraphQLBundle\Command\GraphQLDumpSchemaCommand;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GraphDumpSchemaCommandTest extends TestCase
{
    /**
     * @var Command
     */
    private $command;

    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var string
     */
    private $cacheDir;

    public function setUp()
    {
        parent::setUp();
        $client = static::createClient(['test_case' => 'connection']);
        $kernel = $client->getKernel();

        $application = new Application($kernel);
        $application->add(new GraphQLDumpSchemaCommand());
        $this->command = $application->find('graphql:dump-schema');
        $this->commandTester = new CommandTester($this->command);
        $this->cacheDir = $kernel->getCacheDir();
    }

    /**
     * @param $format
     * @param bool $withFormatOption
     * @dataProvider formatDataProvider
     */
    public function testDump($format, $withFormatOption = true)
    {
        $file = $this->cacheDir.'/schema.'.$format;

        $input = [
            'command' => $this->command->getName(),
            '--file' => $file,
        ];

        if ($withFormatOption) {
            $input['--format'] = $format;
        }
        $this->commandTester->execute($input);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertEquals(trim(file_get_contents(__DIR__.'/fixtures/schema.'.$format)), trim(file_get_contents($file)));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown format "fake".
     */
    public function testInvalidFormat()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--format' => 'fake',
        ]);
    }

    public function formatDataProvider()
    {
        return [
            ['json', false],
            ['json', true],
            ['graphqls'],
        ];
    }
}
