<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Annotations\Reader;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\MetadataParser;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

class AnnotationParser extends MetadataParser
{
    public const METADATA_FORMAT = '@%s';

    protected static Reader $annotationReader;

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

    protected static function getAnnotationReader(): PsrCachedReader
    {
        if (!isset(self::$annotationReader)) {
            if (!class_exists(AnnotationReader::class) || !class_exists(AnnotationRegistry::class)) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException("In order to use annotations, you need to install 'doctrine/annotations' first. See: 'https://www.doctrine-project.org/projects/annotations.html'");
                // @codeCoverageIgnoreEnd
            }

            if (class_exists(AnnotationRegistry::class)) {
                AnnotationRegistry::registerLoader('class_exists');
            }
            $cacheKey = md5(__DIR__);
            // @codeCoverageIgnoreStart
            if (extension_loaded('apcu') && apcu_enabled()) {
                $annotationCache = new ApcuAdapter($cacheKey);
            } else {
                $annotationCache = new PhpFilesAdapter($cacheKey);
            }
            // @codeCoverageIgnoreEnd

            self::$annotationReader = new PsrCachedReader(new AnnotationReader(), $annotationCache, true);
        }

        return self::$annotationReader;
    }
}
