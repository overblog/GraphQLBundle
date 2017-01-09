<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Tests;

use Symfony\Component\Process\ProcessBuilder;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    public function assertCodeStandard($pathToCode, $level = null, $fixers = null)
    {
        $phpCSBin = file_exists(__DIR__.'/../bin/php-cs-fixer') ? __DIR__.'/../bin/php-cs-fixer' : __DIR__.'/../../../bin/php-cs-fixer';

        // Run linter in dry-run mode so it changes nothing.
        $csBuilder = new ProcessBuilder([
            $phpCSBin,
            'fix',
            '--dry-run',
            '--diff',
            $pathToCode,
        ]);

        if (null !== $level) {
            $csBuilder->add('--level='.$level);
        }
        if (null !== $fixers) {
            $csBuilder->add('--fixers='.$fixers);
        }

        $process = $csBuilder->getProcess();

        $process->setTimeout(60);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            sprintf(
                'cli "%s" linter reported errors in "%s/": %s',
                $process->getCommandLine(),
                $pathToCode,
                $process->getOutput()
            )
        );
    }
}
