<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\Closure;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Instance;
use Overblog\GraphQLBundle\Error\ResolveErrors;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage as EL;
use Overblog\GraphQLBundle\Generator\Converter\ExpressionConverter;
use Overblog\GraphQLBundle\Generator\Exception\GeneratorException;
use Overblog\GraphQLBundle\Generator\Model\Collection;
use Overblog\GraphQLBundle\Generator\Model\TypeConfig;

class ResolveInstructionBuilder
{
    protected ExpressionConverter $expressionConverter;

    public function __construct(ExpressionConverter $expressionConverter)
    {
        $this->expressionConverter = $expressionConverter;
    }

    /**
     * Builds a resolver closure that contains the compiled result of user-defined
     * expression and optionally the validation logic.
     *
     * Render example (no expression language):
     *
     *      function ($value, $args, $context, $info) use ($services) {
     *          return "Hello, World!";
     *      }
     *
     * Render example (with expression language):
     *
     *      function ($value, $args, $context, $info) use ($services) {
     *          return $services->mutation("my_resolver", $args);
     *      }
     *
     * Render example (with validation):
     *
     *      function ($value, $args, $context, $info) use ($services) {
     *          $validator = $services->createInputValidator(...func_get_args());
     *          return $services->mutation("create_post", $validator]);
     *      }
     *
     * Render example (with validation, but errors are injected into the user-defined resolver):
     * {@link https://github.com/overblog/GraphQLBundle/blob/master/docs/validation/index.md#injecting-errors}
     *
     *      function ($value, $args, $context, $info) use ($services) {
     *          $errors = new ResolveErrors();
     *          $validator = $services->createInputValidator(...func_get_args());
     *
     *          $errors->setValidationErrors($validator->validate(null, false))
     *
     *          return $services->mutation("create_post", $errors);
     *      }
     *
     * @param string|mixed $resolve
     *
     * @throws GeneratorException
     *
     * @return GeneratorInterface|string
     */
    public function build(TypeConfig $typeConfig, $resolve, ?string $currentField = null, ?array $groups = null)
    {
        if (is_callable($resolve) && is_array($resolve)) {
            return Collection::numeric($resolve);
        }

        // TODO: before creating an input validator, check if any validation rules are defined
        if (EL::isStringWithTrigger($resolve)) {
            $closure = Closure::new()
                ->addArguments('value', 'args', 'context', 'info')
                ->bindVar(TypeGenerator::GRAPHQL_SERVICES);

            $injectValidator = EL::expressionContainsVar('validator', $resolve);

            if ($this->configContainsValidation($typeConfig, $currentField)) {
                $injectErrors = EL::expressionContainsVar('errors', $resolve);

                if ($injectErrors) {
                    $closure->append('$errors = ', Instance::new(ResolveErrors::class));
                }

                $gqlServices = TypeGenerator::GRAPHQL_SERVICES_EXPR;
                $closure->append('$validator = ', "{$gqlServices}->createInputValidator(...func_get_args())");

                // If auto-validation on or errors are injected
                if (!$injectValidator || $injectErrors) {
                    if (!empty($groups)) {
                        $validationGroups = Collection::numeric($groups);
                    } else {
                        $validationGroups = 'null';
                    }

                    $closure->emptyLine();

                    if ($injectErrors) {
                        $closure->append('$errors->setValidationErrors($validator->validate(', $validationGroups, ', false))');
                    } else {
                        $closure->append('$validator->validate(', $validationGroups, ')');
                    }

                    $closure->emptyLine();
                }
            } elseif ($injectValidator) {
                throw new GeneratorException('Unable to inject an instance of the InputValidator. No validation constraints provided. Please remove the "validator" argument from the list of dependencies of your resolver or provide validation configs.');
            }

            $closure->append('return ', $this->expressionConverter->convert($resolve));

            return $closure;
        }

        return ArrowFunction::new($resolve);
    }

    /**
     * Checks if given config contains any validation rules.
     */
    protected function configContainsValidation(TypeConfig $typeConfig, ?string $currentField): bool
    {
        // FIXME this strange solution used to save current strange behavior :) It MUST BE refactored!!!
        $currentField ??= array_key_last($typeConfig->fields);
        $fieldConfig = $typeConfig->fields[$currentField];

        if (!empty($fieldConfig['validation'])) {
            return true;
        }

        foreach ($fieldConfig['args'] ?? [] as $argConfig) {
            if (!empty($argConfig['validation'])) {
                return true;
            }
        }

        return false;
    }
}
