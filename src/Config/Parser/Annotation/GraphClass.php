<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use function class_exists;

class GraphClass extends ReflectionClass
{
    private static ?AnnotationReader $annotationReader = null;

    protected array $annotations = [];

    protected array $propertiesExtended = [];

    /**
     * @param mixed $className
     */
    public function __construct($className)
    {
        parent::__construct($className);

        $annotationReader = self::getAnnotationReader();
        $this->annotations = $annotationReader->getClassAnnotations($this);

        $reflection = $this;
        do {
            foreach ($reflection->getProperties() as $property) {
                if (isset($this->propertiesExtended[$property->getName()])) {
                    continue;
                }
                $this->propertiesExtended[$property->getName()] = $property;
            }
        } while ($reflection = $reflection->getParentClass());
    }

    /**
     * @return ReflectionProperty[]
     */
    public function getPropertiesExtended()
    {
        return $this->propertiesExtended;
    }

    /**
     * @param ReflectionMethod|ReflectionProperty|null $from
     *
     * @return array
     */
    public function getAnnotations(object $from = null)
    {
        if (!$from) {
            return $this->annotations;
        }

        if ($from instanceof ReflectionMethod) {
            return self::getAnnotationReader()->getMethodAnnotations($from);
        }

        if ($from instanceof ReflectionProperty) {
            return self::getAnnotationReader()->getPropertyAnnotations($from);
        }

        /** @phpstan-ignore-next-line */
        throw new AnnotationException(sprintf('Unable to retrieve annotations from object of class "%s".', get_class($from)));
    }

    private static function getAnnotationReader(): AnnotationReader
    {
        if (null === self::$annotationReader) {
            if (!class_exists(AnnotationReader::class) ||
                !class_exists(AnnotationRegistry::class)) {
                throw new RuntimeException('In order to use graphql annotation, you need to require doctrine annotations');
            }

            AnnotationRegistry::registerLoader('class_exists');
            self::$annotationReader = new AnnotationReader();
        }

        return self::$annotationReader;
    }
}
