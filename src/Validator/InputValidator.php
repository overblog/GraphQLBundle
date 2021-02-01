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

class InputValidator
{
    private const TYPE_PROPERTY = 'property';
    private const TYPE_GETTER = 'getter';
    public const CASCADE = 'cascade';

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
     * @param string|array|null $groups
     *
     * @throws ArgumentsValidationException
     */
    public function validate($groups = null, bool $throw = true): ?ConstraintViolationListInterface
    {
        $rootNode = new ValidationNode(
            $this->info->parentType,
            $this->info->fieldName,
            null,
            $this->resolverArgs
        );

        $classMapping = $this->mergeClassValidation();

        $this->buildValidationTree(
            $rootNode,
            $this->info->fieldDefinition->config['args'],
            $classMapping,
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

    private function mergeClassValidation(): array
    {
        $common = static::normalizeConfig($this->info->parentType->config['validation'] ?? []);
        $specific = static::normalizeConfig($this->info->fieldDefinition->config['validation'] ?? []);

        return array_filter([
            'link' => $specific['link'] ?? $common['link'] ?? null,
            'constraints' => [
                ...($common['constraints'] ?? []),
                ...($specific['constraints'] ?? []),
            ],
        ]);
    }

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
     * Creates a composition of ValidationNode objects from args
     * and simultaneously applies to them validation constraints.
     */
    protected function buildValidationTree(ValidationNode $rootObject, array $fields, array $classValidation, array $inputData): ValidationNode
    {
        $metadata = new ObjectMetadata($rootObject);

        if (!empty($classValidation)) {
            $this->applyClassValidation($metadata, $classValidation);
        }

        foreach ($fields as $name => $arg) {
            $property = $arg['name'] ?? $name;
            $config = static::normalizeConfig($arg['validation'] ?? []);

            if (isset($config['cascade']) && isset($inputData[$property])) {
                $groups = $config['cascade'];
                $argType = $this->unclosure($arg['type']);

                /** @var ObjectType|InputObjectType $type */
                $type = Type::getNamedType($argType);

                if (static::isListOfType($argType)) {
                    $rootObject->$property = $this->createCollectionNode($inputData[$property], $type, $rootObject);
                } else {
                    $rootObject->$property = $this->createObjectNode($inputData[$property], $type, $rootObject);
                }

                $valid = new Valid();

                if (!empty($groups)) {
                    $valid->groups = $groups;
                }

                $metadata->addPropertyConstraint($property, $valid);
            } else {
                $rootObject->$property = $inputData[$property] ?? null;
            }

            $config = static::normalizeConfig($config);

            foreach ($config as $key => $value) {
                switch ($key) {
                    case 'link':
                        [$fqcn, $property, $type] = $value;

                        if (!in_array($fqcn, $this->cachedMetadata)) {
                            $this->cachedMetadata[$fqcn] = $this->defaultValidator->getMetadataFor($fqcn);
                        }

                        // Get metadata from the property and it's getters
                        $propertyMetadata = $this->cachedMetadata[$fqcn]->getPropertyMetadata($property);

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

                        break;
                    case 'constraints':
                        $metadata->addPropertyConstraints($property, $value);
                        break;
                    case 'cascade':
                        break;
                }
            }
        }

        $this->metadataFactory->addMetadata($metadata);

        return $rootObject;
    }

    /**
     * @param GeneratedTypeInterface|ListOfType|NonNull $type
     */
    private static function isListOfType($type): bool
    {
        if ($type instanceof ListOfType || ($type instanceof NonNull && $type->getOfType() instanceof ListOfType)) {
            return true;
        }

        return false;
    }

    /**
     * @param ObjectType|InputObjectType $type
     */
    private function createCollectionNode(array $values, $type, ValidationNode $parent): array
    {
        $collection = [];

        foreach ($values as $value) {
            $collection[] = $this->createObjectNode($value, $type, $parent);
        }

        return $collection;
    }

    /**
     * @param ObjectType|InputObjectType $type
     */
    private function createObjectNode(array $value, $type, ValidationNode $parent): ValidationNode
    {
        $classValidation = static::normalizeConfig($type->config['validation'] ?? []);

        return $this->buildValidationTree(
            new ValidationNode($type, null, $parent, $this->resolverArgs),
            self::unclosure($type->config['fields']),
            $classValidation,
            $value
        );
    }

    private function applyClassValidation(ObjectMetadata $metadata, array $rules): void
    {
        $rules = static::normalizeConfig($rules);

        foreach ($rules as $key => $value) {
            switch ($key) {
                case 'link':
                    $linkedMetadata = $this->defaultValidator->getMetadataFor($value);
                    $metadata->addConstraints($linkedMetadata->getConstraints());
                    break;
                case 'constraints':
                    foreach ($this->unclosure($value) as $constraint) {
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
     * Restructures short forms into the full form array and
     * unwraps constraints in closures.
     *
     * @param mixed $config
     */
    public static function normalizeConfig($config): array
    {
        if ($config instanceof Closure) {
            return ['constraints' => $config()];
        }

        if (self::CASCADE === $config) {
            return ['cascade' => []];
        }

        if (isset($config['constraints']) && $config['constraints'] instanceof Closure) {
            $config['constraints'] = $config['constraints']();
        }

        return $config;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function unclosure($value)
    {
        if ($value instanceof Closure) {
            return $value();
        }

        return $value;
    }

    /**
     * @param string|array|null $groups
     *
     * @throws ArgumentsValidationException
     */
    public function __invoke($groups = null, bool $throw = true): ?ConstraintViolationListInterface
    {
        return $this->validate($groups, $throw);
    }
}
