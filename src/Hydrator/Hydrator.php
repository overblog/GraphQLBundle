<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Hydrator\Annotation\Field;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class Hydrator
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function hydrate(ArgumentInterface $args, ResolveInfo $info)
    {
        $requestedField = $info->parentType->getField($info->fieldName);

        foreach ($args->getArrayCopy() as $argName => $input) {
            $argType = $requestedField->getArg($argName)->getType(); /** @var Type $argType */
            $unwrappedType = Type::getNamedType($argType);

            // If no 'model' is set
            if (!isset($unwrappedType->config['model']) /* || Type::isBuiltInType($unwrappedType) */) {
                continue;
            }

            $model = new $unwrappedType->config['model']();
            $reader = AnnotationParser::getAnnotationReader();
            $reflectionClass = new \ReflectionClass($model);
            $annotationMapping = $this->readAnnotationMapping($reflectionClass);

            foreach ($input as $fieldName => $fieldValue) {
                if (isset($annotationMapping[$fieldName])) {
                    $fieldName = $annotationMapping[$fieldName];
                }

                if (property_exists($model, $fieldName)) {
                    $model->$fieldName = $this->convertValue($fieldValue, $reflectionClass);
                }
            }

        }

        return 'something';
    }

    public function readAnnotationMapping(\ReflectionClass $reflectionClass): array
    {
        $reader = AnnotationParser::getAnnotationReader();
        $properties = $reflectionClass->getProperties();

        $mapping = [];
        foreach ($properties as $property) {
            $annotation = $reader->getPropertyAnnotation($property, Field::class);

            if (isset($annotation->name)) {
                $mapping[$annotation->name] = $property->name;
            }
        }

        return $mapping;
    }

    /**
     * @param mixed $value
     * @param string $property
     * @param object $model
     * @return mixed
     */
    private function convertValue($value, $reflectionClass)
    {
        // Converters defined
        return $value;
    }

    private function readAnnotation(object $model, string $property): ?object
    {
        // TODO(murtukov): optimize this line
        $reflectionClass = new \ReflectionClass($model);

        $reader = AnnotationParser::getAnnotationReader();

        return $reader->getPropertyAnnotation(
            $reflectionClass->getProperty($property),
            Field::class
        );
    }
}
