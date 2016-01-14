<?php

namespace Overblog\GraphBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ConfigExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction('resolver', function ($arg) {
                return sprintf('$this->get(%s)', $arg);
            }, function (array $variables, $value) {
                return $variables['container']->get($value);
            }),

            new ExpressionFunction('globalId', function ($idValue)   {
                return sprintf('base64_encode($info->parentType->name. ":" . %s)', $idValue);
            }, function (array $variables, $idValue) {
                $name = $variables['info']->parentType->name;

                return base64_encode(sprintf('%s:%s', $name, $idValue));
            }),

            new ExpressionFunction('fromGlobalId', function ($globalId) {
                return sprintf('explode(":", base64_decode(%s), 2)', $globalId);
            }, function (array $variables, $globalId) {
                return explode(':', base64_decode($globalId), 2);
            }),
        ];
    }
}
