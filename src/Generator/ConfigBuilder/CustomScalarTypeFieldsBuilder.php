<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator\ConfigBuilder;

use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Murtukov\PHPCodeGenerator\Utils;
use Overblog\GraphQLBundle\Generator\Collection;
use Overblog\GraphQLBundle\Generator\Exception\GeneratorException;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;

class CustomScalarTypeFieldsBuilder implements ConfigBuilderInterface
{
    public function build(TypeConfig $typeConfig, Collection $builder, PhpFile $phpFile): void
    {
        // only by custom-scalar types
        if ($typeConfig->isCustomScalar()) {
            if (isset($typeConfig->scalarType)) {
                $builder->addItem('scalarType', $typeConfig->scalarType);
            }

            if (isset($typeConfig->serialize)) {
                $builder->addItem('serialize', $this->buildScalarCallback($typeConfig->serialize, 'serialize', $typeConfig, $phpFile));
            }

            if (isset($typeConfig->parseValue)) {
                $builder->addItem('parseValue', $this->buildScalarCallback($typeConfig->parseValue, 'parseValue', $typeConfig, $phpFile));
            }

            if (isset($typeConfig->parseLiteral)) {
                $builder->addItem('parseLiteral', $this->buildScalarCallback($typeConfig->parseLiteral, 'parseLiteral', $typeConfig, $phpFile));
            }
        }
    }

    /**
     * Builds an arrow function that calls a static method.
     *
     * Render example:
     *
     *      fn() => MyClassName::myMethodName(...\func_get_args())
     *
     * @param callable|mixed $callback - a callable string or a callable array
     *
     * @throws GeneratorException
     */
    protected function buildScalarCallback($callback, string $fieldName, TypeConfig $typeConfig, PhpFile $phpFile): ArrowFunction
    {
        if (!is_callable($callback)) {
            throw new GeneratorException("Value of '$fieldName' is not callable.");
        }

        $closure = new ArrowFunction();

        if (\is_array($callback)) {
            [$class, $method] = $callback;
        } elseif(\is_string($callback)) {
            [$class, $method] = explode('::', $callback);
        } else {
            throw new GeneratorException(sprintf('Invalid type of "%s" value passed.', $fieldName));
        }

        $className = Utils::resolveQualifier($class);

        if ($className === $typeConfig->class_name) {
            // Create an alias if name of serializer is same as type name
            $className = 'Base' . $className;
            $phpFile->addUse($class, $className);
        } else {
            $phpFile->addUse($class);
        }

        $closure->setExpression(Literal::new("$className::$method(...\\func_get_args())"));

        return $closure;
    }
}
