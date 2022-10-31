<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Mapping;

use Overblog\GraphQLBundle\Validator\ValidationNode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;

final class ObjectMetadata extends ClassMetadata
{
    public function __construct(ValidationNode $object)
    {
        parent::__construct($object->getName());
    }

    public function addPropertyConstraint(string $property, Constraint $constraint): static
    {
        if (!isset($this->properties[$property])) {
            /** @phpstan-ignore-next-line */
            $this->properties[$property] = new PropertyMetadata($property);

            $this->addPropertyMetadata($this->properties[$property]);
        }

        $constraint->addImplicitGroupName($this->getDefaultGroup());

        $this->properties[$property]->addConstraint($constraint);

        return $this;
    }

    private function addPropertyMetadata(PropertyMetadataInterface $metadata): void
    {
        $property = $metadata->getPropertyName();
        /** @phpstan-ignore-next-line */
        $this->members[$property][] = $metadata;
    }
}
