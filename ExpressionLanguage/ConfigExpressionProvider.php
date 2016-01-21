<?php

namespace Overblog\GraphBundle\ExpressionLanguage;

use Overblog\GraphBundle\Resolver\ResolverInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ConfigExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new ExpressionFunction('service', function ($arg) {
                return sprintf('$this->container->get(%s)', $arg);
            }, function (array $variables, $value) {
                return $variables['container']->get($value);
            }),

            new ExpressionFunction('parameter', function ($arg) {
                return sprintf('$this->container->getParameter(%s)', $arg);
            }, function (array $variables, $value) {
                return $variables['container']->getParameter($value);
            }),

            new ExpressionFunction('resolver', function ($name, array $args = []) {
                return sprintf('$this->container->get("overblog_graph.relsover_resolver")->resolve(%s)->resolve($args)', $name);
            }, function (array $variables, $name, array $args = []) {
                $resolver =  $variables['container']->get('overblog_graph.relsover_resolver')->resolve($name);

                if ($resolver instanceof ResolverInterface || is_callable([$resolver, 'resolve'])) {
                    return $resolver->resolve($args);
                } elseif (is_callable($resolver)) {
                    return call_user_func_array($resolver, [$args]);
                } else {
                    throw new \RuntimeException(
                        sprintf(
                            'Resolver must be callable or instance of "%s"',
                            'Overblog\\GraphBundle\\Resolver\\ResolverInterface'
                        )
                    );
                }
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
