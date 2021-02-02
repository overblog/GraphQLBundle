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

class AnnotationParser extends MetadataParser
{
    const METADATA_FORMAT = '@%s';

    protected static ?Reader $annotationReader = null;

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
        if (null === self::$annotationReader) {
            if (!class_exists(AnnotationReader::class) ||
                !class_exists(AnnotationRegistry::class)) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('In order to use graphql annotations, you need to require doctrine annotations');
                // @codeCoverageIgnoreEnd
            }

            AnnotationRegistry::registerLoader('class_exists');
            $cacheKey = md5(__DIR__);
            // @codeCoverageIgnoreStart
            if (extension_loaded('apcu') && apcu_enabled()) {
                $annotationCache = new ApcuCache();
            } else {
                $annotationCache = new PhpFileCache(sys_get_temp_dir().$cacheKey);
            }
            // @codeCoverageIgnoreEnd
            $annotationCache->setNamespace($cacheKey);

            self::$annotationReader = new CachedReader(new AnnotationReader(), $annotationCache, true);
        }

        return self::$annotationReader;
    }
}
