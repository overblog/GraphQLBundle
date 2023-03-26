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
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class AnnotationParser extends MetadataParser
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

    public static function getAnnotationReader(): Reader
    {
        if (!isset(self::$annotationReader)) {
            if (!class_exists(AnnotationReader::class)) {
                throw new ServiceNotFoundException("In order to use annotations, you need to install 'doctrine/annotations' first. See: 'https://www.doctrine-project.org/projects/annotations.html'");
            }
            if (!class_exists(ApcuAdapter::class)) {
                throw new ServiceNotFoundException("In order to use annotations, you need to install 'symfony/cache' first. See: 'https://symfony.com/doc/current/components/cache.html'");
            }

            if (class_exists(AnnotationRegistry::class) && method_exists(AnnotationRegistry::class, 'registerLoader')) {
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
