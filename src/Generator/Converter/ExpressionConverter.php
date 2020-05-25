<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Converter;

use Murtukov\PHPCodeGenerator\ConverterInterface;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Expression;
use function strpos;

class ExpressionConverter implements ConverterInterface
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    function convert($value): string
    {
        return (string) $this->expressionLanguage->evaluate(new Expression(\substr($value, 2)));
    }

    function check($value): bool
    {
        return strpos($value, ExpressionLanguage::EXPRESSION_LANGUAGE_TRIGGER) === 0;
    }

    function maybeConvert(string $value)
    {
        if ($this->check($value)) {
            return $this->convert($value);
        } else {
            return $value;
        }
    }
}
