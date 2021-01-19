<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser;

use Overblog\GraphQLBundle\Config\Parser\MetadataParser\ClassesTypesMap;
use ReflectionClass;
use Reflector;

abstract class TypeGuesser implements TypeGuesserInterface
{
    protected ClassesTypesMap $map;

    public function __construct(ClassesTypesMap $map)
    {
        $this->map = $map;
    }

    abstract public function supports(Reflector $reflector): bool;

    abstract public function getName(): string;

    abstract public function guessType(ReflectionClass $reflectionClass, Reflector $reflector, array $filterGraphQLTypes = []): ?string;
}
