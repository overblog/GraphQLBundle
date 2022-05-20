<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Mapping;

use ReflectionException;
use ReflectionProperty;
use ReturnTypeWillChange;
use Symfony\Component\Validator\Mapping\MemberMetadata;

class PropertyMetadata extends MemberMetadata
{
    public function __construct(string $name)
    {
        parent::__construct('anonymous', $name, $name);
    }

    /**
     * @param object|string $object
     *
     * @throws ReflectionException
     */
    protected function newReflectionMember($object): ReflectionProperty
    {
        $member = new ReflectionProperty($object, $this->getName());
        $member->setAccessible(true);

        return $member;
    }

    /**
     * @param object|string $object
     *
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function getPropertyValue($object)
    {
        return $this->getReflectionMember($object)->getValue($object);
    }
}
