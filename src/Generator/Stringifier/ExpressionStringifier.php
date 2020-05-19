<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\Stringifier;

use Murtukov\PHPCodeGenerator\StringifierInterface;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Expression;
use function strpos;

class ExpressionStringifier implements StringifierInterface
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    function stringify($value): string
    {
        return $this->expressionLanguage->compile(new Expression(\substr($value, 2)));
    }

    function check($value): bool
    {
        return strpos($value, ExpressionLanguage::EXPRESSION_LANGUAGE_TRIGGER) === 0;
    }
}
