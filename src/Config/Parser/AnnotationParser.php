<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\PhpFileCache;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\MetadataParser;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use RuntimeException;
use function apcu_enabled;
use function class_exists;
use function extension_loaded;
use function md5;
use function sys_get_temp_dir;

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

    protected static function getAnnotationReader(): Reader
    {
        if (!isset(self::$annotationReader)) {
            if (!class_exists(AnnotationReader::class) || !class_exists(AnnotationRegistry::class)) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException("In order to use annotations, you need to install 'doctrine/annotations' first. See: 'https://www.doctrine-project.org/projects/annotations.html'");
                // @codeCoverageIgnoreEnd
            }

            AnnotationRegistry::registerLoader('class_exists');
            $cacheKey = md5(__DIR__);
            // @codeCoverageIgnoreStart
            if (extension_loaded('apcu') && apcu_enabled()) {
                $annotationCache = new ApcuCache();
            } else {
                $annotationCache = new PhpFileCache(join(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), $cacheKey]));
            }
            // @codeCoverageIgnoreEnd
            $annotationCache->setNamespace($cacheKey);

            self::$annotationReader = new CachedReader(new AnnotationReader(), $annotationCache, true);
        }

        return self::$annotationReader;
    }
}
