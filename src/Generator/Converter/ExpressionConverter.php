<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Converter;

use Murtukov\PHPCodeGenerator\ConverterInterface;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;

class ExpressionConverter implements ConverterInterface
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param mixed $value
     *
     * @return mixed|string
     */
    public function convert($value)
    {
        return $this->expressionLanguage->compile(
            ExpressionLanguage::unprefixExpression($value),
            ExpressionLanguage::KNOWN_NAMES
        );
    }

    /**
     * @param mixed $maybeExpression
     */
    public function check($maybeExpression): bool
    {
        return ExpressionLanguage::isStringWithTrigger($maybeExpression);
    }
}
