<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use GraphQL\Type\Definition\ResolveInfo;
use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage as EL;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Generator\Model\Collection;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;

class IsTypeOfBuilder implements ConfigBuilderInterface
{
    private ExpressionConverter $expressionConverter;

    public function __construct(ExpressionConverter $expressionConverter)
    {
        $this->expressionConverter = $expressionConverter;
    }

    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        if (isset($typeConfig->isTypeOf)) {
            $builder->addItem('isTypeOf', $this->buildIsTypeOf($typeConfig->isTypeOf));
        }
    }

    /**
     * Builds an arrow function from a string with an expression prefix,
     * otherwise just returns the provided value back untouched.
     *
     * Render example:
     *
     *      fn($className) => (($className = "App\\ClassName") && $value instanceof $className)
     *
     * @param mixed $isTypeOf
     */
    private function buildIsTypeOf($isTypeOf): ArrowFunction
    {
        if (EL::isStringWithTrigger($isTypeOf)) {
            $expression = $this->expressionConverter->convert($isTypeOf);

            return ArrowFunction::new(Literal::new($expression), 'bool')
                ->setStatic()
                ->addArguments('value', 'context')
                ->addArgument('info', ResolveInfo::class);
        }

        return ArrowFunction::new($isTypeOf);
    }
}
