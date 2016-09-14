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

use Overblog\GraphQLBundle\Command\GraphDumpSchemaCommand;
use Overblog\GraphQLBundle\Tests\Functional\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GraphDumpSchemaCommandTest extends TestCase
{
    public function testExecute()
    {
        $client = static::createClient(['test_case' => 'connection']);
        $kernel = $client->getKernel();

        $application = new Application($kernel);
        $application->add(new GraphDumpSchemaCommand());

        $command = $application->find('graph:dump-schema');
        $file = $kernel->getCacheDir().'/schema.json';

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--file' => $file,
            ]
        );

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertEquals(trim(file_get_contents(__DIR__.'/schema.json')), file_get_contents($file));
    }
}
