<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser;

use ReflectionClass;
use ReflectionProperty;

interface TypeGuesserInterface
{
    public function guessType(ReflectionClass $reflectionClass, ReflectionProperty $reflector): ?string;
}
