<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
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

class InputValidator
{
    private const TYPE_PROPERTY = 'property';
    private const TYPE_GETTER = 'getter';

    /**
     * @var array
     */
    private $resolverArgs;

    /**
     * @var array
     */
    private $constraintMapping;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var ResolveInfo
     */
    private $info;

    /**
     * @var ValidatorFactory
     */
    private $validatorFactory;

    /**
     * @var ClassMetadataInterface[]
     */
    private $cachedMetadata = [];

    /**
     * InputValidator constructor.
     *
     * @param array                   $resolverArgs
     * @param ValidatorInterface|null $validator
     * @param ValidatorFactory        $factory
     * @param array                   $mapping
     */
    public function __construct(array $resolverArgs, ?ValidatorInterface $validator, ValidatorFactory $factory, array $mapping)
    {
        if (null === $validator) {
            throw new ServiceNotFoundException(
                "The 'validator' service is not found. To use the 'InputValidator' you need to install the 
                Symfony Validator Component first. See: 'https://symfony.com/doc/current/validation.html'"
            );
        }

        $this->resolverArgs = $this->mapResolverArgs($resolverArgs);
        $this->info = $this->resolverArgs['info'];
        $this->constraintMapping = $mapping;
        $this->validator = $validator;
        $this->validatorFactory = $factory;
        $this->metadataFactory = new MetadataFactory();
    }

    /**
     * Converts a numeric array of resolver args to an associative one.
     *
     * @param array $rawReolverArgs
     *
     * @return array
     */
    private function mapResolverArgs(array $rawReolverArgs)
    {
        return [
            'value' => $rawReolverArgs[0],
            'args' => $rawReolverArgs[1],
            'context' => $rawReolverArgs[2],
            'info' => $rawReolverArgs[3],
        ];
    }

    /**
     * @param string|array|null $groups
     * @param bool              $throw
     *
     * @return ConstraintViolationListInterface|null
     *
     * @throws ArgumentsValidationException
     */
    public function validate($groups = null, bool $throw = true): ?ConstraintViolationListInterface
    {
        $rootObject = new ValidationNode($this->info->parentType, $this->info->fieldName, null, $this->resolverArgs);

        $this->buildValidationTree($rootObject, $this->constraintMapping, $this->resolverArgs['args']->getArrayCopy());

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
     *
     * @param ValidationNode $rootObject
     * @param array          $constraintMapping
     * @param array          $args
     *
     * @return ValidationNode
     */
    protected function buildValidationTree(ValidationNode $rootObject, array $constraintMapping, array $args): ValidationNode
    {
        $metadata = new ObjectMetadata($rootObject);

        $this->applyClassConstraints($metadata, $constraintMapping['class']);

        foreach ($constraintMapping['properties'] as $property => $params) {
            if (!empty($params['cascade']) && isset($args[$property])) {
                $options = $params['cascade'];
                $type = $this->info->schema->getType($options['referenceType']);

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

            foreach ($params ?? [] as $key => $value) {
                if (null === $value) {
                    continue;
                }

                switch ($key) {
                    case 'link':
                        [$fqcn, $property, $type] = $value;

                        if (!\in_array($fqcn, $this->cachedMetadata)) {
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
     * @param array                      $values
     * @param ObjectType|InputObjectType $type
     * @param ValidationNode             $parent
     *
     * @return array
     */
    private function createCollectionNode(array $values, Type $type, ValidationNode $parent): array
    {
        $collection = [];

        foreach ($values as $value) {
            $collection[] = $this->createObjectNode($value, $type, $parent);
        }

        return $collection;
    }

    /**
     * @param array                      $value
     * @param ObjectType|InputObjectType $type
     * @param $parent
     *
     * @return ValidationNode
     */
    private function createObjectNode(array $value, Type $type, ValidationNode $parent): ValidationNode
    {
        $mapping = [
            'class' => $type->config['validation'] ?? null,
        ];

        foreach ($type->getFields() as $fieldName => $inputField) {
            $mapping['properties'][$fieldName] = $inputField->config['validation'];
        }

        return $this->buildValidationTree(new ValidationNode($type, null, $parent, $this->resolverArgs), $mapping, $value);
    }

    /**
     * @param ObjectMetadata $metadata
     * @param array          $constraints
     */
    private function applyClassConstraints(ObjectMetadata $metadata, ?array $constraints): void
    {
        foreach ($constraints ?? [] as $key => $value) {
            if (null === $value) {
                continue;
            }

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

    /**
     * @throws ArgumentsValidationException
     */
    public function __invoke($groups = null, bool $throw = true): ?ConstraintViolationListInterface
    {
        return $this->validate($groups, $throw);
    }
}
