<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator;

use Closure;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Definition\ResolverArgs;
use Overblog\GraphQLBundle\Definition\Type\GeneratedTypeInterface;
use Overblog\GraphQLBundle\Validator\Exception\ArgumentsValidationException;
use Overblog\GraphQLBundle\Validator\Mapping\MetadataFactory;
use Overblog\GraphQLBundle\Validator\Mapping\ObjectMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\GetterMetadata;
use Symfony\Component\Validator\Mapping\PropertyMetadata;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function in_array;

final class InputValidator
{
    private const TYPE_PROPERTY = 'property';
    private const TYPE_GETTER = 'getter';
    private const CASCADE = 'cascade';

    private ResolverArgs $resolverArgs;
    private ValidatorInterface $defaultValidator;
    private MetadataFactory $metadataFactory;
    private ResolveInfo $info;
    private ConstraintValidatorFactoryInterface $constraintValidatorFactory;
    private ?TranslatorInterface $defaultTranslator;

    /** @var ClassMetadataInterface[] */
    private array $cachedMetadata = [];

    public function __construct(
        ResolverArgs $resolverArgs,
        ValidatorInterface $validator,
        ConstraintValidatorFactoryInterface $constraintValidatorFactory,
        ?TranslatorInterface $translator
    ) {
        $this->resolverArgs = $resolverArgs;
        $this->info = $this->resolverArgs->info;
        $this->defaultValidator = $validator;
        $this->constraintValidatorFactory = $constraintValidatorFactory;
        $this->defaultTranslator = $translator;
        $this->metadataFactory = new MetadataFactory();
    }

    /**
     * Entry point.
     *
     * @throws ArgumentsValidationException
     */
    public function validate(string|array|null $groups = null, bool $throw = true): ?ConstraintViolationListInterface
    {
        $rootNode = new ValidationNode(
            $this->info->parentType,
            $this->info->fieldName,
            null,
            $this->resolverArgs
        );

        $this->buildValidationTree(
            $rootNode,
            $this->info->fieldDefinition->config['args'] ?? [],
            $this->getClassLevelConstraints(),
            $this->resolverArgs->args->getArrayCopy()
        );

        $validator = $this->createValidator($this->metadataFactory);
        $errors = $validator->validate($rootNode, null, $groups);

        if ($throw && $errors->count() > 0) {
            throw new ArgumentsValidationException($errors);
        } else {
            return $errors;
        }
    }

    /**
     * Creates a validator with a custom metadata factory. The metadata factory
     * is used to properly map validation constraints to ValidationNode objects.
     */
    private function createValidator(MetadataFactory $metadataFactory): ValidatorInterface
    {
        $builder = Validation::createValidatorBuilder()
            ->setMetadataFactory($metadataFactory)
            ->setConstraintValidatorFactory($this->constraintValidatorFactory);

        if (null !== $this->defaultTranslator) {
            // @phpstan-ignore-next-line (only for Symfony 4.4)
            $builder
                ->setTranslator($this->defaultTranslator)
                ->setTranslationDomain('validators');
        }

        return $builder->getValidator();
    }

    /**
     * Either returns an existing metadata object related to the ValidationNode
     * object or creates a new one.
     */
    private function getMetadata(ValidationNode $rootObject): ObjectMetadata
    {
        // Return existing metadata if present
        if ($this->metadataFactory->hasMetadataFor($rootObject)) {
            return $this->metadataFactory->getMetadataFor($rootObject);
        }

        // Create new metadata and add it to the factory
        $metadata = new ObjectMetadata($rootObject);
        $this->metadataFactory->addMetadata($metadata);

        return $metadata;
    }

    /**
     * Creates a map of ValidationNode objects from args and simultaneously
     * applies validation constraints to them.
     */
    private function buildValidationTree(ValidationNode $rootObject, iterable $fields, array $classValidation, array $inputData): ValidationNode
    {
        $metadata = $this->getMetadata($rootObject);

        if (!empty($classValidation)) {
            $this->applyClassConstraints($metadata, $classValidation);
        }

        foreach ($fields as $name => $arg) {
            $property = $arg['name'] ?? $name;
            $config = self::normalizeConfig($arg['validation'] ?? []);

            if ($this->shouldCascade($config, $inputData, $property)) {
                $this->handleCascade($rootObject, $property, $arg, $config, $inputData[$property]);
                continue; // delegated to nested object
            }

            // assign scalar/null value when not cascading
            $rootObject->$property = $inputData[$property] ?? null;

            if ($metadata->hasPropertyMetadata($property)) {
                continue;
            }

            $this->applyPropertyConstraints($metadata, $property, self::normalizeConfig($config));
        }

        return $rootObject;
    }

    private function shouldCascade(array $config, array $inputData, string|int $property): bool
    {
        return isset($config['cascade']) && isset($inputData[$property]);
    }

    /**
     * Creates a new nested ValidationNode object or a collection of them based
     * on the type of the argument and applies the cascade validation.
     */
    private function handleCascade(ValidationNode $rootObject, string|int $property, array $arg, array $config, mixed $value): void
    {
        $argType = self::unclosure($arg['type']);

        /** @var ObjectType|InputObjectType $type */
        $type = Type::getNamedType($argType);

        if (self::isListOfType($argType)) {
            $rootObject->$property = $this->createCollectionNode($value, $type, $rootObject);
        } else {
            $rootObject->$property = $this->createObjectNode($value, $type, $rootObject);
        }

        // Mark the property for recursive validation
        $this->addValidConstraint($this->getMetadata($rootObject), (string) $property, $config['cascade']);
    }

    /**
     * Applies the Assert\Valid constraint to enable a recursive validation.
     *
     * @link https://symfony.com/doc/current/reference/constraints/Valid.html Docs
     */
    private function addValidConstraint(ObjectMetadata $metadata, string $property, array $groups): void
    {
        $valid = new Valid();
        if (!empty($groups)) {
            $valid->groups = $groups;
        }

        $metadata->addPropertyConstraint($property, $valid);
    }

    /**
     * Adds property constraints to the metadata object related to a ValidationNode object.
     */
    private function applyPropertyConstraints(ObjectMetadata $metadata, string|int $property, array $config): void
    {
        foreach ($config as $key => $value) {
            switch ($key) {
                case 'link':
                    // Add constraints from the linked class
                    $this->addLinkedConstraints((string) $property, $value, $metadata);
                    break;
                case 'constraints':
                    // Add constraints from the yml config directly
                    $metadata->addPropertyConstraints((string) $property, $value);
                    break;
                case 'cascade':
                    // Cascade validation was already handled recursively.
                    break;
            }
        }
    }

    private function addLinkedConstraints(string $property, array $link, ObjectMetadata $metadata, ): void
    {
        [$fqcn, $classProperty, $type] = $link;

        if (!in_array($fqcn, $this->cachedMetadata)) {
            /** @phpstan-ignore-next-line */
            $this->cachedMetadata[$fqcn] = $this->defaultValidator->getMetadataFor($fqcn);
        }

        // Get metadata from the property and its getters
        $propertyMetadata = $this->cachedMetadata[$fqcn]->getPropertyMetadata($classProperty);

        foreach ($propertyMetadata as $memberMetadata) {
            // Allow only constraints specified by the "link" matcher
            if (self::TYPE_GETTER === $type) {
                if (!$memberMetadata instanceof GetterMetadata) {
                    continue;
                }
            } elseif (self::TYPE_PROPERTY === $type) {
                if (!$memberMetadata instanceof PropertyMetadata) {
                    continue;
                }
            }

            $metadata->addPropertyConstraints($property, $memberMetadata->getConstraints());
        }
    }


    private function createCollectionNode(array $values, ObjectType|InputObjectType $type, ValidationNode $parent): array
    {
        $collection = [];

        foreach ($values as $value) {
            $collection[] = $this->createObjectNode($value, $type, $parent);
        }

        return $collection;
    }

    private function createObjectNode(array $value, ObjectType|InputObjectType $type, ValidationNode $parent): ValidationNode
    {
        /** @phpstan-ignore-next-line */
        $classValidation = self::normalizeConfig($type->config['validation'] ?? []);

        return $this->buildValidationTree(
            new ValidationNode($type, null, $parent, $this->resolverArgs),
            self::unclosure($type->config['fields']),
            $classValidation,
            $value
        );
    }

    private function applyClassConstraints(ObjectMetadata $metadata, array $rules): void
    {
        $rules = self::normalizeConfig($rules);

        foreach ($rules as $key => $value) {
            switch ($key) {
                case 'link':
                    $linkedMetadata = $this->defaultValidator->getMetadataFor($value);
                    $metadata->addConstraints($linkedMetadata->getConstraints());
                    break;
                case 'constraints':
                    foreach (self::unclosure($value) as $constraint) {
                        if ($constraint instanceof Constraint) {
                            $metadata->addConstraint($constraint);
                        } elseif ($constraint instanceof GroupSequence) {
                            $metadata->setGroupSequence($constraint);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Since all GraphQL arguments and fields are represented by ValidationNode
     * objects, it is possible to define constraints at the class level.
     *
     * Class level constraints can be defined in three different ways:
     * - linked to an existing class/entity
     * - defined per field
     * - defined per type
     *
     * This method merges all of them into a single array and returns it.
     *
     * @link https://github.com/overblog/GraphQLBundle/blob/master/docs/validation/index.md#applying-of-validation-constraints
     */
    private function getClassLevelConstraints(): array
    {
        $typeLevel = self::normalizeConfig($this->info->parentType->config['validation'] ?? []);
        $fieldLevel = self::normalizeConfig($this->info->fieldDefinition->config['validation'] ?? []);

        return array_filter([
            'link' => $fieldLevel['link'] ?? $typeLevel['link'] ?? null,
            'constraints' => [
                ...($typeLevel['constraints'] ?? []),
                ...($fieldLevel['constraints'] ?? []),
            ],
        ]);
    }

    /**
     * Restructures short forms into the full form array and unwraps
     * constraints in closures.
     *
     * @param Closure $config
     */
    private static function normalizeConfig(mixed $config): array
    {
        if ($config instanceof Closure) {
            return ['constraints' => $config()];
        }

        if (InputValidator::CASCADE === $config) {
            return ['cascade' => []];
        }

        if (isset($config['constraints']) && $config['constraints'] instanceof Closure) {
            $config['constraints'] = $config['constraints']();
        }

        return $config;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function unclosure($value): mixed
    {
        if ($value instanceof Closure) {
            return $value();
        }

        return $value;
    }

    private static function isListOfType(GeneratedTypeInterface|ListOfType|NonNull $type): bool
    {
        if ($type instanceof ListOfType || ($type instanceof NonNull && $type->getWrappedType() instanceof ListOfType)) {
            return true;
        }

        return false;
    }

    /**
     * @throws ArgumentsValidationException
     */
    public function __invoke(array|string|null $groups = null, bool $throw = true): ?ConstraintViolationListInterface
    {
        return $this->validate($groups, $throw);
    }
}
