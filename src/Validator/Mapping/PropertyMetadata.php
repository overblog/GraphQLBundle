<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Mapping;

use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Validator\Mapping\MemberMetadata;

final class PropertyMetadata extends MemberMetadata
{
    public function __construct(string $name)
    {
        parent::__construct('anonymous', $name, $name);
    }

    /**
     * @param object|string $objectOrClassName
     *
     * @throws ReflectionException
     */
    protected function newReflectionMember($objectOrClassName): ReflectionProperty
    {
        $member = new ReflectionProperty($objectOrClassName, $this->getName());
        $member->setAccessible(true);

        return $member;
    }

    #[ReturnTypeWillChange]
    public function getPropertyValue(mixed $containingValue): mixed
    {
        return $this->getReflectionMember($containingValue)->getValue($containingValue);
    }
}
