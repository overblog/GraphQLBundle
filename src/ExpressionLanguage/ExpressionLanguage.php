<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\Token;
use function array_merge;
use function strlen;
use function strpos;
use function substr;

class ExpressionLanguage extends BaseExpressionLanguage
{
    // TODO: make names
    public const KNOWN_NAMES = ['value', 'args', 'context', 'info', 'object', 'validator', 'errors', 'childrenComplexity', 'typeName', 'fieldName'];
    public const EXPRESSION_TRIGGER = '@=';

    private array $globalNames = [];

    /**
     * @param $index
     * @param $name
     */
    public function addGlobalName($index, $name): void
    {
        $this->globalNames[$index] = $name;
    }

    public function getGlobalNames()
    {
        return $this->globalNames;
    }

    public function compile($expression, $names = [])
    {
        return parent::compile($expression, array_merge($names, $this->globalNames));
    }

    /**
     * Checks if expression string containst specific variable.
     *
     * Argument can be either an Expression object or a string with or
     * without a prefix
     *
     * @param string            $name       - Name of the searched variable
     * @param string|Expression $expression - Expression to search in
     *
     * @throws SyntaxError
     */
    public static function expressionContainsVar(string $name, $expression): bool
    {
        if ($expression instanceof Expression) {
            $expression = $expression->__toString();
        } elseif (self::stringHasTrigger($expression)) {
            $expression = self::unprefixExpression($expression);
        }

        $stream = (new Lexer())->tokenize($expression);
        $current = &$stream->current;

        while (!$stream->isEOF()) {
            if ($name === $current->value && Token::NAME_TYPE === $current->type) {
                // Also check that it's not a functions name
                $stream->next();
                if ("(" !== $current->value) {
                    $contained = true;
                    break;
                }
                continue;
            }

            $stream->next();
        }

        return $contained ?? false;
    }

    /**
     * Checks if a string has the expression trigger prefix.
     *
     * @param string $maybeExpression
     */
    public static function stringHasTrigger(string $maybeExpression): bool
    {
        return strpos($maybeExpression, self::EXPRESSION_TRIGGER) === 0;
    }
//
    /**
     * Removes the expression trigger prefix from a string. If no prefix found,
     * returns the initial string.
     *
     * @param string $expression - String expression with a trigger prefix
     * @return false|string
     */
    public static function unprefixExpression(string $expression)
    {
        $string = substr($expression, strlen(self::EXPRESSION_TRIGGER));

        return (false !== $string) ? $string : $expression;
    }
}
