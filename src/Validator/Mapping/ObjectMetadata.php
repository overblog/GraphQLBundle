<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Mapping;

use Overblog\GraphQLBundle\Validator\ValidationNode;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadata;

final class ObjectMetadata extends ClassMetadata
{
    public function __construct(ValidationNode $object)
    {
        parent::__construct($object->getName());
    }

    public function addPropertyConstraint(string $property, Constraint $constraint): static
    {
        // Use reflection to access private properties
        $propertiesReflection = new ReflectionProperty(ClassMetadata::class, 'properties');
        $propertiesReflection->setAccessible(true);
        $properties = $propertiesReflection->getValue($this);

        if (!isset($properties[$property])) {
            // Create our custom PropertyMetadata with single argument
            $propertyMetadata = new PropertyMetadata($property);
            $properties[$property] = $propertyMetadata;
            $propertiesReflection->setValue($this, $properties);

            // Use reflection to call private addPropertyMetadata method
            $addPropertyMetadataReflection = new ReflectionMethod(ClassMetadata::class, 'addPropertyMetadata');
            $addPropertyMetadataReflection->setAccessible(true);
            $addPropertyMetadataReflection->invoke($this, $propertyMetadata);

            // Refresh properties array after addPropertyMetadata call
            $properties = $propertiesReflection->getValue($this);
        }

        $constraint->addImplicitGroupName($this->getDefaultGroup());

        // Add constraint to the property metadata
        $properties[$property]->addConstraint($constraint);

        return $this;
    }
}
