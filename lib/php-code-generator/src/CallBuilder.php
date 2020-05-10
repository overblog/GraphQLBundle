<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;


class CallBuilder extends DependencyAwareGenerator
{
    private array $chainParts = [];


    public static function start(string $qualifier)
    {

    }

    public function add(string $name, ...$args)
    {

    }

    public function addStatic(string $name, ...$args) {

    }

    public function generate(): string
    {
        return '';
    }
}