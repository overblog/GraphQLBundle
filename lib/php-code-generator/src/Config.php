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

    /**
     * @var StringifierInterface[]
     */
    public static array $stringifiers = [
        StringifierInterface::TYPE_STRING => [],
        StringifierInterface::TYPE_BOOL => [],
        StringifierInterface::TYPE_DOUBLE => [],
        StringifierInterface::TYPE_INT => [],
        StringifierInterface::TYPE_OBJECT => [],
    ];

    /**
     * Registers user defined stringifiers.
     *
     * @param StringifierInterface $stringifier
     * @param string $type
     */
    public static function registerStringifier(StringifierInterface $stringifier, string $type)
    {
        self::$stringifiers[$type][get_class($stringifier)] = $stringifier;
    }
}
