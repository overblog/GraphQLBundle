<?php

namespace Murtukov\PHPCodeGenerator;

/**
 * Helper class for conditional builders
 */
class Mock
{
    private static self $instance;
    private static object $caller;

    public static function getInstance(object $caller): self
    {
        self::$caller = $caller;
        return self::$instance ?? self::$instance = new self();
    }

    public function __call($name, $arguments)
    {
        return self::$caller;
    }
}