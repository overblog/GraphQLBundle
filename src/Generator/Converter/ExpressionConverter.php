<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Converter;

use Murtukov\PHPCodeGenerator\ConverterInterface;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use function is_string;
use function strpos;
use function substr;

class ExpressionConverter implements ConverterInterface
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    function convert($value)
    {
        return $this->expressionLanguage->compile(substr($value, 2), ExpressionLanguage::KNOWN_NAMES);
    }

    function check($value): bool
    {
        if (is_string($value)) {
            return strpos($value, ExpressionLanguage::EXPRESSION_LANGUAGE_TRIGGER) === 0;
        }

        return false;
    }

    function maybeConvert($value)
    {
        if ($this->check($value)) {
            return $this->convert($value);
        } else {
            return $value;
        }
    }

    public function __invoke($value)
    {
        return $this->maybeConvert($value);
    }

    public function getExpressionLanguage()
    {
        return $this->expressionLanguage;
    }
}
