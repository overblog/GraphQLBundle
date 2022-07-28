<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Generator;

use Murtukov\PHPCodeGenerator\ArrowFunction;
use Murtukov\PHPCodeGenerator\GeneratorInterface;
use Murtukov\PHPCodeGenerator\Instance;
use Murtukov\PHPCodeGenerator\Literal;
use Murtukov\PHPCodeGenerator\PhpFile;
use Overblog\GraphQLBundle\Generator\Exception\GeneratorException;
use Overblog\GraphQLBundle\Generator\Model\Collection;
use Overblog\GraphQLBundle\Generator\Model\ValidationConfig;
use Overblog\GraphQLBundle\Validator\InputValidator;

class ValidationRulesBuilder
{
    public const CONSTRAINTS_NAMESPACE = 'Symfony\Component\Validator\Constraints';

    /**
     * Render example:
     *
     *      [
     *          'link' => {@see normalizeLink}
     *          'cascade' => [
     *              'groups' => ['my_group'],
     *          ],
     *          'constraints' => {@see buildConstraints}
     *      ]
     *
     * If only constraints provided, uses {@see buildConstraints} directly.
     *
     * @param array{
     *     constraints: array,
     *     link: string,
     *     cascade: array
     * } $config
     *
     * @throws GeneratorException
     */
    public function build(array $config, PhpFile $phpFile): GeneratorInterface
    {
        // Convert to object for better readability
        $validationConfig = new ValidationConfig($config);

        $array = Collection::assoc();

        if (!empty($validationConfig->link)) {
            if (false === strpos($validationConfig->link, '::')) {
                // e.g. App\Entity\Droid
                $array->addItem('link', $validationConfig->link);
            } else {
                // e.g. App\Entity\Droid::$id
                $array->addItem('link', Collection::numeric($this->normalizeLink($validationConfig->link)));
            }
        }

        if (isset($validationConfig->cascade)) {
            // If there are only constarainst, use short syntax
            if (empty($validationConfig->cascade['groups'])) {
                $phpFile->addUse(InputValidator::class);

                return Literal::new('InputValidator::CASCADE');
            }
            $array->addItem('cascade', $validationConfig->cascade['groups']);
        }

        if (!empty($validationConfig->constraints)) {
            // If there are only constarainst, use short syntax
            if (0 === $array->count()) {
                return $this->buildConstraints($phpFile, $validationConfig->constraints);
            }
            $array->addItem('constraints', $this->buildConstraints($phpFile, $validationConfig->constraints));
        }

        return $array;
    }

    /**
     * Builds a closure or a numeric multiline array with Symfony Constraint
     * instances. The array is used by {@see InputValidator} during requests.
     *
     * Render example (array):
     *
     *      [
     *          new NotNull(),
     *          new Length([
     *              'min' => 5,
     *              'max' => 10
     *          ]),
     *          ...
     *      ]
     *
     * Render example (in a closure):
     *
     *      fn() => [
     *          new NotNull(),
     *          new Length([
     *              'min' => 5,
     *              'max' => 10
     *          ]),
     *          ...
     *      ]
     *
     * @throws GeneratorException
     *
     * @return ArrowFunction|Collection
     */
    protected function buildConstraints(PhpFile $phpFile, array $constraints = [], bool $inClosure = true)
    {
        $result = Collection::numeric()->setMultiline();

        foreach ($constraints as $wrapper) {
            $name = key($wrapper);
            $args = reset($wrapper);

            if (false !== strpos($name, '\\')) {
                // Custom constraint
                $fqcn = ltrim($name, '\\');
                $instance = Instance::new("@\\$fqcn");
            } else {
                // Symfony constraint
                $fqcn = static::CONSTRAINTS_NAMESPACE."\\$name";
                $phpFile->addUse(static::CONSTRAINTS_NAMESPACE.' as SymfonyConstraints');
                $instance = Instance::new("@SymfonyConstraints\\$name");
            }

            if (!class_exists($fqcn)) {
                throw new GeneratorException("Constraint class '$fqcn' doesn't exist.");
            }

            if (is_array($args)) {
                if (isset($args[0]) && is_array($args[0])) {
                    // Nested instance
                    $instance->addArgument($this->buildConstraints($phpFile, $args, false));
                } elseif (isset($args['constraints'][0]) && is_array($args['constraints'][0])) {
                    // Nested instance with "constraints" key (full syntax)
                    $options = [
                        'constraints' => $this->buildConstraints($phpFile, $args['constraints'], false),
                    ];

                    // Check for additional options
                    foreach ($args as $key => $option) {
                        if ('constraints' === $key) {
                            continue;
                        }
                        $options[$key] = $option;
                    }

                    $instance->addArgument($options);
                } else {
                    // Numeric or Assoc array?
                    $instance->addArgument(isset($args[0]) ? $args : Collection::assoc($args));
                }
            } elseif (null !== $args) {
                $instance->addArgument($args);
            }

            $result->push($instance);
        }

        if ($inClosure) {
            return ArrowFunction::new($result);
        }

        return $result; // @phpstan-ignore-line
    }

    /**
     * Creates and array from a formatted string.
     *
     * Examples:
     *
     *      "App\Entity\User::$firstName"  -> ['App\Entity\User', 'firstName', 'property']
     *      "App\Entity\User::firstName()" -> ['App\Entity\User', 'firstName', 'getter']
     *      "App\Entity\User::firstName"   -> ['App\Entity\User', 'firstName', 'member']
     */
    protected function normalizeLink(string $link): array
    {
        [$fqcn, $classMember] = explode('::', $link);

        if ('$' === $classMember[0]) {
            return [$fqcn, ltrim($classMember, '$'), 'property'];
        }
        if (')' === substr($classMember, -1)) {
            return [$fqcn, rtrim($classMember, '()'), 'getter'];
        }
        return [$fqcn, $classMember, 'member'];
    }
}
