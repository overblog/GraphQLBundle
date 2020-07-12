<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Config\Parser\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use function class_exists;

class GraphClass extends ReflectionClass
{
    private static ?AnnotationReader $annotationReader = null;

    protected array $annotations = [];

    protected array $propertiesExtended = [];

    public function __construct(string $className)
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
     * Get an array of parent class names.
     *
     * @return string[]
     */
    public function getParents(): array
    {
        $parents = [];
        $class = $this;
        while ($parent = $class->getParentClass()) {
            $parents[] = $parent->getName();
            $class = $parent;
        }

        return $parents;
    }

    /**
     * Get the list of methods name.
     *
     * @return string[]
     */
    public function getMethodsNames(): array
    {
        return array_map(fn (ReflectionMethod $method) => $method->getName(), $this->getMethods());
    }

    public function getMethodAnnotations(string $name): array
    {
        return self::getAnnotationReader()->getMethodAnnotations($this->getMethod($name));
    }

    public function getPropertyAnnotations(string $name): array
    {
        return self::getAnnotationReader()->getPropertyAnnotations($this->getProperty($name));
    }

    /**
     * @return ReflectionProperty[]
     */
    public function getPropertiesExtended()
    {
        return $this->propertiesExtended;
    }

    public function getPropertyExtended(string $name): ReflectionProperty
    {
        if (!isset($this->propertiesExtended[$name])) {
            throw new ReflectionException(sprintf('Missing property %s on class or parent class %s', $name, $this->getName()));
        }

        return $this->propertiesExtended[$name];
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
