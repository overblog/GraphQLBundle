<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage as EL;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Generator\Model\Collection;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;

class ResolveTypeBuilder implements ConfigBuilderInterface
{
    private ExpressionConverter $expressionConverter;

    public function __construct(ExpressionConverter $expressionConverter)
    {
        $this->expressionConverter = $expressionConverter;
    }

    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        if (isset($typeConfig->resolveType)) {
            $builder->addItem('resolveType', $this->buildResolveType($typeConfig->resolveType));
        }
    }

    /**
     * Builds an arrow function from a string with an expression prefix,
     * otherwise just returns the provided value back untouched.
     *
     * Render example:
     *
     *      fn($value, $context, $info) => $services->getType($value)
     *
     * @param mixed $resolveType
     *
     * @return mixed|ArrowFunction
     */
    protected function buildResolveType($resolveType)
    {
        if (EL::isStringWithTrigger($resolveType)) {
            $expression = $this->expressionConverter->convert($resolveType);

            return ArrowFunction::new()
                ->addArguments('value', 'context', 'info')
                ->setExpression(Literal::new($expression));
        }

        return $resolveType;
    }
}
