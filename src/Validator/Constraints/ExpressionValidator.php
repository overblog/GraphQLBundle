<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Constraints;

use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Validator\ValidationNode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ExpressionValidator extends \Symfony\Component\Validator\Constraints\ExpressionValidator
{
    private ExpressionLanguage $expressionLanguage;

    private GraphQLServices $graphQLServices;

    public function __construct(ExpressionLanguage $expressionLanguage, GraphQLServices $graphQLServices)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->graphQLServices = $graphQLServices;

        parent::__construct($expressionLanguage); // @phpstan-ignore-line
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Expression) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Expression');
        }

        $variables = $constraint->values;
        $variables['value'] = $value;
        $variables[TypeGenerator::GRAPHQL_SERVICES] = $this->graphQLServices;

        $object = $this->context->getObject();

        $variables['this'] = $object;

        if ($object instanceof ValidationNode) {
            $variables['parentValue'] = $object->getResolverArg('value');
            $variables['context'] = $object->getResolverArg('context');
            $variables['args'] = $object->getResolverArg('args');
            $variables['info'] = $object->getResolverArg('info');
        }

        // Make all tagged GraphQL public services available in the expression constraint
        $this->addGlobalVariables($constraint->expression, $variables);

        if (!$this->expressionLanguage->evaluate($constraint->expression, $variables)) {
            $this->context->buildViolation($constraint->message)
                          ->setParameter('{{ value }}', $this->formatValue($value, self::OBJECT_TO_STRING))
                          ->setCode(Expression::EXPRESSION_FAILED_ERROR)
                          ->addViolation();
        }
    }

    /**
     * @param string|\Symfony\Component\ExpressionLanguage\Expression $expression
     */
    private function addGlobalVariables($expression, array &$variables): void
    {
        $globalVariables = $this->expressionLanguage->getGlobalNames();
        foreach (ExpressionLanguage::extractExpressionVarNames($expression) as $extractExpressionVarName) {
            if (
                isset($variables[$extractExpressionVarName])
                || !$this->graphQLServices->has($extractExpressionVarName)
                || !in_array($extractExpressionVarName, $globalVariables)
            ) {
                continue;
            }

            $variables[$extractExpressionVarName] = $this->graphQLServices->get($extractExpressionVarName);
        }
    }
}
