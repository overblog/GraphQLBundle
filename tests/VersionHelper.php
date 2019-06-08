<?php

namespace Overblog\GraphQLBundle\Tests;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class VersionHelper
{
    const KEYS_WEIGHT = [
        'message' => 100,
        'extensions' => 90,
        'locations' => 80,
        'path' => 70,
    ];

    public static function normalizedPayload(array $payload)
    {
        if (self::needNormalized()) {
            if (!empty($payload['errors'])) {
                $payload['errors'] = self::normalizedErrors($payload['errors']);
            }

            if (!empty($payload['extensions']['warnings'])) {
                $payload['extensions']['warnings'] = self::normalizedErrors($payload['extensions']['warnings']);
            }
        }

        return $payload;
    }

    public static function normalizedErrors(array $errors)
    {
        if (self::needNormalized()) {
            foreach ($errors as &$error) {
                foreach ($error as $key => $value) {
                    if (!\in_array($key, ['message', 'locations', 'path', 'extensions'])) {
                        $error['extensions'] = isset($error['extensions']) ? $error['extensions'] : [];
                        $error['extensions'][$key] = $value;
                        unset($error[$key]);
                    }
                }

                \uksort($error, function ($key1, $key2) {
                    return self::KEYS_WEIGHT[$key1] > self::KEYS_WEIGHT[$key2] ? -1 : 1;
                });
            }
        }

        return $errors;
    }

    public static function needNormalized()
    {
        return self::compareWebonyxGraphQLPHPVersion('0.13', '>=');
    }

    public static function compareWebonyxGraphQLPHPVersion($version, $operator)
    {
        return \version_compare(self::webonyxGraphQLPHPVersion(), $version, $operator);
    }

    public static function webonyxGraphQLPHPVersion()
    {
        static $version = null;

        if (empty($version)) {
            $composerBinPath = \file_exists(__DIR__.'/../composer.phar') ? __DIR__.'/../composer.phar' : '`which composer`';
            $commandline = $composerBinPath.' show \'webonyx/graphql-php\' | grep \'versions\' | grep -o -E \'\*\ .+\' | cut -d\' \' -f2 | cut -d\',\' -f1';
            if (\is_callable([Process::class, 'fromShellCommandline'])) {
                $process = Process::fromShellCommandline($commandline);
            } else {
                $process = new Process($commandline);
            }
            $process->setWorkingDirectory(__DIR__.'/../');
            $process->run();
            if ($process->isSuccessful()) {
                $version = $process->getOutput();
                $version = \preg_replace('/[^\.0-9]/', '', $version);
            } else {
                throw new ProcessFailedException($process);
            }
        }

        return $version;
    }
}
