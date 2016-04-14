<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Resolver\Config;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Resolver\ArgResolver;
use Overblog\GraphQLBundle\Resolver\ConfigResolver;
use Overblog\GraphQLBundle\Resolver\FieldResolver;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractConfigSolution implements ConfigSolutionInterface
{
    /**
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var TypeResolver
     */
    protected $typeResolver;

    /**
     * @var FieldResolver
     */
    protected $fieldResolver;

    /**
     * @var ArgResolver
     */
    protected $argResolver;

    /**
     * @var ConfigResolver
     */
    protected $configResolver;
    /**
     * @param ExpressionLanguage $expressionLanguage
     *
     * @return AbstractConfigSolution
     */
    public function setExpressionLanguage($expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;

        return $this;
    }

    /**
     * @param TypeResolver $typeResolver
     *
     * @return AbstractConfigSolution
     */
    public function setTypeResolver($typeResolver)
    {
        $this->typeResolver = $typeResolver;

        return $this;
    }

    /**
     * @param FieldResolver $fieldResolver
     *
     * @return AbstractConfigSolution
     */
    public function setFieldResolver($fieldResolver)
    {
        $this->fieldResolver = $fieldResolver;

        return $this;
    }

    /**
     * @param ArgResolver $argResolver
     *
     * @return AbstractConfigSolution
     */
    public function setArgResolver($argResolver)
    {
        $this->argResolver = $argResolver;

        return $this;
    }

    /**
     * @param ConfigResolver $configResolver
     *
     * @return AbstractConfigSolution
     */
    public function setConfigResolver($configResolver)
    {
        $this->configResolver = $configResolver;

        return $this;
    }

    protected function solveUsingExpressionLanguageIfNeeded($expression, array $values = [])
    {
        if ($this->isExpression($expression)) {
            return $this->expressionLanguage->evaluate(substr($expression, 2), $values);
        }

        return $expression;
    }

    protected function isExpression($expression)
    {
        return is_string($expression) &&  0 === strpos($expression, '@=');
    }

    protected function solveResolveCallbackArgs()
    {
        $args = func_get_args();
        $optionResolver = new OptionsResolver();
        $optionResolver->setDefaults([null, null, null]);

        $args = $optionResolver->resolve($args);

        $arg1IsResolveInfo = $args[1] instanceof ResolveInfo;

        $value = $args[0];
        /** @var ResolveInfo $info */
        $info = $arg1IsResolveInfo ? $args[1] : $args[2];
        /** @var Argument $resolverArgs */
        $resolverArgs = new Argument(!$arg1IsResolveInfo ? $args[1] : []);

        return [
            'value' => $value,
            'args' => $resolverArgs,
            'info' => $info,
        ];
    }
}
