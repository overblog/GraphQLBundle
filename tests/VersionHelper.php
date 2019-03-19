<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class VersionHelper
{
    public static function compareWebonyxGraphQLPHPVersion($version, $operator): bool
    {
        return \version_compare(self::webonyxGraphQLPHPVersion(), $version, $operator);
    }

    public static function webonyxGraphQLPHPVersion(): string
    {
        static $version = null;

        if (empty($version)) {
            $composerBinPath = \file_exists(__DIR__.'/../composer.phar') ? __DIR__.'/../composer.phar' : '`which composer`';
            $process = new Process($composerBinPath.' show \'webonyx/graphql-php\' | grep \'versions\' | grep -o -E \'\*\ .+\' | cut -d\' \' -f2 | cut -d\',\' -f1');
            $process->setWorkingDirectory(__DIR__.'/../');
            $process->run();
            if ($process->isSuccessful()) {
                $version = $process->getOutput();
                $version = \preg_replace('/[^.0-9]/', '', $version);
            } else {
                throw new ProcessFailedException($process);
            }
        }

        return $version;
    }
}
