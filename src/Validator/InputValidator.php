<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Validator\Exception\ArgumentsValidationException;
use Overblog\GraphQLBundle\Validator\Mapping\MetadataFactory;
use Overblog\GraphQLBundle\Validator\Mapping\ObjectMetadata;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\GetterMetadata;
use Symfony\Component\Validator\Mapping\PropertyMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function in_array;

class InputValidator
{
    private const TYPE_PROPERTY = 'property';
    private const TYPE_GETTER = 'getter';

    private array $resolverArgs;
    private array $propertiesMapping;
    private array $classMapping;
    private ValidatorInterface $validator;
    private MetadataFactory $metadataFactory;
    private ResolveInfo $info;
    private ValidatorFactory $validatorFactory;

    /** @var ClassMetadataInterface[] */
    private array $cachedMetadata = [];

    /**
     * InputValidator constructor.
     */
    public function __construct(
        array $resolverArgs,
        ?ValidatorInterface $validator,
        ValidatorFactory $factory,
        array $propertiesMapping = [],
        array $classMapping = []
    ) {
        if (null === $validator) {
            throw new ServiceNotFoundException(
                "The 'validator' service is not found. To use the 'InputValidator' you need to install the
                Symfony Validator Component first. See: 'https://symfony.com/doc/current/validation.html'"
            );
        }

        $this->resolverArgs = $this->mapResolverArgs(...$resolverArgs);
        $this->info = $this->resolverArgs['info'];
        $this->propertiesMapping = $propertiesMapping;
        $this->classMapping = $classMapping;
        $this->validator = $validator;
        $this->validatorFactory = $factory;
        $this->metadataFactory = new MetadataFactory();
    }

    /**
     * Converts a numeric array of resolver args to an associative one.
     *
     * @param mixed $value
     * @param mixed $context
     */
    private function mapResolverArgs($value, ArgumentInterface $args, $context, ResolveInfo $info): array
    {
        return [
            'value' => $value,
            'args' => $args,
            'context' => $context,
            'info' => $info,
        ];
    }

    /**
     * @param string|array|null $groups
     *
     * @throws ArgumentsValidationException
     */
    public function validate($groups = null, bool $throw = true): ?ConstraintViolationListInterface
    {
        $rootObject = new ValidationNode($this->info->parentType, $this->info->fieldName, null, $this->resolverArgs);

        $this->buildValidationTree(
            $rootObject,
            $this->propertiesMapping,
            $this->classMapping,
            $this->resolverArgs['args']->getArrayCopy()
        );

        $validator = $this->validatorFactory->createValidator($this->metadataFactory);

        $errors = $validator->validate($rootObject, null, $groups);

        if ($throw && $errors->count() > 0) {
            throw new ArgumentsValidationException($errors);
        } else {
            return $errors;
        }
    }

    /**
     * Creates a composition of ValidationNode objects from args
     * and simultaneously applies to them validation constraints.
     */
    protected function buildValidationTree(ValidationNode $rootObject, array $propertiesMapping, array $classMapping, array $args): ValidationNode
    {
        $metadata = new ObjectMetadata($rootObject);

        if (!empty($classMapping)) {
            $this->applyClassConstraints($metadata, $classMapping);
        }

        foreach ($propertiesMapping as $property => $params) {
            if (!empty($params['cascade']) && isset($args[$property])) {
                $options = $params['cascade'];

                /** @var ObjectType|InputObjectType $type */
                $type = $options['referenceType'];

                if ($options['isCollection']) {
                    $rootObject->$property = $this->createCollectionNode($args[$property], $type, $rootObject);
                } else {
                    $rootObject->$property = $this->createObjectNode($args[$property], $type, $rootObject);
                }

                $valid = new Valid();

                if (!empty($options['groups'])) {
                    $valid->groups = $options['groups'];
                }

                $metadata->addPropertyConstraint($property, $valid);
            } else {
                $rootObject->$property = $args[$property] ?? null;
            }

            $this->restructureShortForm($params);

            foreach ($params ?? [] as $key => $value) {
                switch ($key) {
                    case 'link':
                        [$fqcn, $property, $type] = $value;

                        if (!in_array($fqcn, $this->cachedMetadata)) {
                            $this->cachedMetadata[$fqcn] = $this->validator->getMetadataFor($fqcn);
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
        $classMapping = $type->config['validation'] ?? [];
        $propertiesMapping = [];

        foreach ($type->getFields() as $fieldName => $inputField) {
            $propertiesMapping[$fieldName] = $inputField->config['validation'];
        }

        return $this->buildValidationTree(
            new ValidationNode($type, null, $parent, $this->resolverArgs),
            $propertiesMapping,
            $classMapping,
            $value
        );
    }

    private function applyClassConstraints(ObjectMetadata $metadata, array $rules): void
    {
        $this->restructureShortForm($rules);

        foreach ($rules as $key => $value) {
            switch ($key) {
                case 'link':
                    $linkedMetadata = $this->validator->getMetadataFor($value);
                    $metadata->addConstraints($linkedMetadata->getConstraints());
                    break;
                case 'constraints':
                    foreach ($value as $constraint) {
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

    private function restructureShortForm(array &$rules): void
    {
        if (isset($rules[0])) {
            $rules = ['constraints' => $rules];
        }
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
