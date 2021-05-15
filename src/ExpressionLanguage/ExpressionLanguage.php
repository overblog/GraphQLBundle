<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\Token;
use function array_merge;
use function strlen;
use function substr;

final class ExpressionLanguage extends BaseExpressionLanguage
{
    // TODO (murtukov): make names conditional
    public const KNOWN_NAMES = ['value', 'args', 'context', 'info', 'object', 'validator', 'errors', 'childrenComplexity', 'typeName', 'fieldName'];
    public const EXPRESSION_TRIGGER = '@=';

    /** @var array<string, string> */
    public array $globalNames = [];

    /** @var array<string, string> */
    public array $expressionVariableServiceIds = [];

    public function addExpressionVariableNameServiceId(string $expressionVarName, string $serviceId): void
    {
        $this->expressionVariableServiceIds[$expressionVarName] = $serviceId;
        $this->addGlobalName(sprintf(TypeGenerator::GRAPHQL_SERVICES.'->get(\'%s\')', $serviceId), $expressionVarName);
    }

    /**
     * @return array<string, string>
     */
    public function getExpressionVariableServiceIds(): array
    {
        return $this->expressionVariableServiceIds;
    }

    public function addGlobalName(string $code, string $expressionVarName): void
    {
        $this->globalNames[$code] = $expressionVarName;
    }

    /**
     * @param string|Expression $expression
     * @param array             $names
     */
    public function compile($expression, $names = []): string
    {
        return parent::compile($expression, array_merge($names, $this->globalNames));
    }

    /**
     * Checks if expression string containst specific variable.
     *
     * Argument can be either an Expression object or a string with or
     * without a prefix
     *
     * @param string            $name       - name of the searched variable (needle)
     * @param string|Expression $expression - expression to search in (haystack)
     *
     * @throws SyntaxError
     */
    public static function expressionContainsVar(string $name, $expression): bool
    {
        foreach (static::extractExpressionVarNames($expression) as $varName) {
            if ($name === $varName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string|Expression $expression - expression to search in (haystack)
     *
     * @throws SyntaxError
     */
    public static function extractExpressionVarNames($expression): iterable
    {
        if ($expression instanceof Expression) {
            $expression = $expression->__toString();
        } elseif (self::stringHasTrigger($expression)) {
            $expression = self::unprefixExpression($expression);
        }

        /** @var string $expression */
        $stream = (new Lexer())->tokenize($expression);
        $current = &$stream->current;
        $isProperty = false;
        $varNames = [];

        while (!$stream->isEOF()) {
            if ('.' === $current->value) {
                $isProperty = true;
            } elseif (Token::NAME_TYPE === $current->type) {
                if (!$isProperty) {
                    $name = $current->value;
                    // Also check that it's not a function's name
                    $stream->next();
                    if ('(' !== $current->value) {
                        $varNames[] = $name;
                    }
                    continue;
                } else {
                    $isProperty = false;
                }
            }

            $stream->next();
        }

        return $varNames;
    }

    /**
     * Checks if value is a string and has the expression trigger prefix.
     *
     * @param mixed $value
     */
    public static function isStringWithTrigger($value): bool
    {
        if (is_string($value)) {
            return self::stringHasTrigger($value);
        }

        return false;
    }

    /**
     * Checks if a string has the expression trigger prefix.
     */
    public static function stringHasTrigger(string $maybeExpression): bool
    {
        return str_starts_with($maybeExpression, self::EXPRESSION_TRIGGER);
    }

    /**
     * Removes the expression trigger prefix from a string. If no prefix found,
     * returns the initial string.
     *
     * @param string $expression - String expression with a trigger prefix
     *
     * @return string
     */
    public static function unprefixExpression(string $expression)
    {
        $string = substr($expression, strlen(self::EXPRESSION_TRIGGER));

        return '' !== $string ? $string : $expression;
    }
}
