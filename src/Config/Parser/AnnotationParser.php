<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\MetadataParser;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use RuntimeException;

class AnnotationParser extends MetadataParser
{
    public const METADATA_FORMAT = '@%s';

    protected static ?AnnotationReader $annotationReader = null;

    protected static function getMetadatas(Reflector $reflector): array
    {
        $reader = self::getAnnotationReader();

        switch (true) {
            case $reflector instanceof ReflectionClass: return $reader->getClassAnnotations($reflector);
            case $reflector instanceof ReflectionMethod: return $reader->getMethodAnnotations($reflector);
            case $reflector instanceof ReflectionProperty: return $reader->getPropertyAnnotations($reflector);
        }

        return [];
    }

    protected static function getAnnotationReader(): AnnotationReader
    {
        if (null === self::$annotationReader) {
            if (!class_exists(AnnotationReader::class) ||
                !class_exists(AnnotationRegistry::class)) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('In order to use graphql annotation, you need to require doctrine annotations');
                // @codeCoverageIgnoreEnd
            }

            AnnotationRegistry::registerLoader('class_exists');
            self::$annotationReader = new AnnotationReader();
        }

        return self::$annotationReader;
    }
}
