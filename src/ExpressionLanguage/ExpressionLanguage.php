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
    // TODO (murtukov): make names conditional
    public const KNOWN_NAMES = ['value', 'args', 'context', 'info', 'object', 'validator', 'errors', 'childrenComplexity', 'typeName', 'fieldName'];
    public const EXPRESSION_TRIGGER = '@=';

    public array $globalNames = [];

    public function addGlobalName(string $index, string $name): void
    {
        $this->globalNames[$index] = $name;
    }

    /**
     * @param string|Expression $expression
     * @param array             $names
     *
     * @return string
     */
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

        /** @var string $expression */
        $stream = (new Lexer())->tokenize($expression);
        $current = &$stream->current;

        while (!$stream->isEOF()) {
            if ($name === $current->value && Token::NAME_TYPE === $current->type) {
                // Also check that it's not a function's name
                $stream->next();
                if ('(' !== $current->value) {
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
     */
    public static function stringHasTrigger(string $maybeExpression): bool
    {
        return 0 === strpos($maybeExpression, self::EXPRESSION_TRIGGER);
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
