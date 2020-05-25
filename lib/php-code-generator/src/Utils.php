<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;

use Murtukov\PHPCodeGenerator\Exception\UnrecognizedValueTypeException;
use function json_encode;

class Utils
{
    const TYPE_STRING = 'string';
    const TYPE_INT = 'integer';
    const TYPE_BOOL = 'boolean';
    const TYPE_DOUBLE = 'double';
    const TYPE_OBJECT = 'object';
    const TYPE_ARRAY = 'array';

    /**
     * @var bool Whether arrays should be split into multiple lines
     */
    private static bool $multiline = false;

    /**
     * @var bool Applies only for array values
     */
    private static bool $withKeys = false;

    /**
     * @var bool If true, null values are not rendered
     */
    private static bool $skipNullValues = false;

    /**
     * @var array Custom converters registered by users
     */
    private static array $customConverters = [];

    /**
     * @param mixed $value
     * @param bool $multiline
     * @param bool $withKeys
     * @param array $converters
     * @return false|string
     * @throws UnrecognizedValueTypeException
     */
    public static function stringify($value, bool $multiline = false, bool $withKeys = false, array $converters = [])
    {
        // Common options to avoid passing them recursively
        self::$multiline = $multiline;
        self::$withKeys = $withKeys;
        self::$customConverters = $converters;

        return self::stringifyValue($value);
    }

    /**
     * @param mixed $value
     * @return false|string
     * @throws UnrecognizedValueTypeException
     */
    private static function stringifyValue($value)
    {
        $type = gettype($value);

        // Custom converters
        if (!empty(self::$customConverters)) {
            foreach (Config::getConverterClasses($type) as $fqcn) {
                $converter = Config::getConverter($fqcn);
                if ($converter->check($value)) {
                    return $converter->convert($value);
                }
            }
        }

        // Default converters
        switch ($type) {
            case 'boolean':
            case 'integer':
            case 'double':
                return json_encode($value);
            case 'string':
                if (empty($value)) {
                    return "''";
                }
                return self::filterString($value);
            case 'array':
                return self::$withKeys ? self::stringifyAssocArray($value) : self::stringifyNumericArray($value);
            case 'object':
                if (!$value instanceof GeneratorInterface) {
                    return json_encode($value->__toString());
                }
                return $value;
            case 'NULL':
                if (self::$skipNullValues) {
                    return '';
                }
                return 'null';
            default:
                throw new UnrecognizedValueTypeException();
        }
    }

    /**
     * @param array $items
     * @return string
     * @throws UnrecognizedValueTypeException
     */
    private static function stringifyAssocArray(array $items): string
    {
        if (empty($items)) {
            return '[]';
        }

        $code = '';

        if (self::$multiline) {
            $code .= "\n";

            foreach ($items as $key => $value) {
                $key = is_int($key) ? $key : "'$key'";
                $value = self::stringifyValue($value);
                $code .= "$key => $value,\n";
            }

            $code = Utils::indent($code);
        } else {
            foreach ($items as $key => $value) {
                $key = is_int($key) ? $key : "'$key'";
                $value = self::stringifyValue($value);
                $code .= "$key => $value, ";
            }
        }

        // Remove last comma
        $code = rtrim($code, ', ');

        return "[$code]";
    }

    /**
     * @param array $items
     * @return string
     * @throws UnrecognizedValueTypeException
     */
    private static function stringifyNumericArray(array $items): string
    {
        if (empty($items)) {
            return '[]';
        }

        $code = '';

        if (self::$multiline) {
            $code .= "\n";

            foreach ($items as $value) {
                $value = self::stringifyValue($value);
                $code .= "$value,\n";
            }

            $code = Utils::indent($code);
        } else {
            foreach ($items as $value) {
                $value = self::stringifyValue($value);
                $code .= "$value, ";
            }
        }

        // Remove last comma
        $code = rtrim($code, ', ');

        return "[$code]";
    }

    private static function filterString(string $string): string
    {
        switch ($string[0]) {
            case Config::$suppressSymbol:
                return substr($string, 1);
            case '$':
                return $string;
            default:
                return json_encode($string);
        }
    }

    public static function indent(string $code): string
    {
        $indent = Config::$indent;
        return $indent . str_replace("\n", "\n$indent", $code);
    }
}
