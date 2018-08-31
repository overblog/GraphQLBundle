<?php

namespace Overblog\GraphQLBundle\Config\Processor;

use Symfony\Component\ExpressionLanguage\Expression;

final class ExpressionProcessor implements ProcessorInterface
{
    const DEFAULT_EXPRESSION_LANGUAGE_TRIGGER = '@=';

    /**
     * {@inheritdoc}
     */
    public static function process(array $configs, $expressionLanguageTrigger = self::DEFAULT_EXPRESSION_LANGUAGE_TRIGGER)
    {
        return \array_map(function ($v) use ($expressionLanguageTrigger) {
            if (\is_array($v)) {
                return static::process($v, $expressionLanguageTrigger);
            } elseif (\is_string($v) && 0 === \strpos($v, $expressionLanguageTrigger)) {
                return new Expression(\substr($v, 2));
            }

            return $v;
        }, $configs);
    }
}
