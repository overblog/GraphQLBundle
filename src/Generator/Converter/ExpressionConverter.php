<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Converter;

use Murtukov\PHPCodeGenerator\ConverterInterface;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use function is_string;

class ExpressionConverter implements ConverterInterface
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    function convert($value)
    {
        return $this->expressionLanguage->compile(
            ExpressionLanguage::unprefixExpression($value),
            ExpressionLanguage::KNOWN_NAMES
        );
    }

    function check($maybeExpression): bool
    {
        if (is_string($maybeExpression)) {
            return ExpressionLanguage::stringHasTrigger($maybeExpression);
        }

        return false;
    }
}
