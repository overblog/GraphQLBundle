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
    private static array $customStringifiers = [
        // e.g.: 'App\Stringifiers\ExpressionStringifier' => object,
    ];

    /**
     * A map of FCQNs and their types registered as custom stringifiers.
     */
    private static array $customStringifiersTypeMap = [
        // e.g.: 'string' => [App\Stringifiers\ExpressionStringifier, App\Stringifiers\AnotherStringifier],
    ];

    /**
     * Registers user defined stringifiers.
     *
     * @param StringifierInterface $stringifierInstance
     * @param string $type
     */
    public static function registerStringifier(StringifierInterface $stringifierInstance, string $type)
    {
        $fqcn = get_class($stringifierInstance);

        self::$customStringifiers[$fqcn] = $stringifierInstance;
        self::$customStringifiersTypeMap[$type][] = $fqcn;
    }

    /**
     * Unregister a previously registered custom stringifier
     *
     * @param string $fqcn - Fully qualified class name
     */
    public static function unregisterStringifier(string $fqcn)
    {
        // Remove instance
        unset (self::$customStringifiers[$fqcn]);
        // Remove map entry
        $type = array_search($fqcn, self::$customStringifiersTypeMap);
        unset (self::$customStringifiersTypeMap[$type]);
    }

    /**
     * Returns an instance of registered custom stringifier.
     *
     * @param string $fqcn
     * @return StringifierInterface|null
     */
    public static function getStringifier(string $fqcn): ?object
    {
        return self::$customStringifiers[$fqcn] ?? null;
    }

    public static function getStringifierClasses(string $type): ?array
    {
        return self::$customStringifiersTypeMap[$type] ?? [];
    }
}
