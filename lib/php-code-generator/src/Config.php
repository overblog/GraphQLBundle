<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;

/**
 * Static config to be used by all generator components
 */
class Config
{
    public static string $indent = "    ";
    public static bool $shortenQualifiers = true;
    public static bool $manageUseStatements = true;
    public static string $suppressSymbol = "@";
}