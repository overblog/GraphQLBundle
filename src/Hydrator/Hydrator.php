<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Hydrator\Annotation\Field;
use ReflectionClass;
use ReflectionException;
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

        $models = new Models();

        foreach ($args->getArrayCopy() as $argName => $input) {
            $argType = $requestedField->getArg($argName)->getType();

            $inputType = $argType->getOfType();

            if (!isset($inputType->config['model'])) {
                continue;
            }

            $models->models[$argName] = $this->hydrateInputType($inputType, $input);
        }

        return $models;
    }

    /**
     * @param mixed $inputValues
     * @throws ReflectionException
     */
    private function hydrateInputType(InputObjectType $inputType, $inputValues)
    {
        if (isset($inputType->config['model'])) {
            $model = new $inputType->config['model'];
        } else {
            return $inputValues;
        }

        $reflectionClass = new ReflectionClass($model);
        $annotationMapping = $this->readAnnotationMapping($reflectionClass);

        foreach ($inputType->getFields() as $fieldName => $fieldObject) {
            if (!isset($inputValues[$fieldName])) {
                continue;
            }

            $mappedName = $annotationMapping[$fieldName] ?? $fieldName;

            if ($this->propertyAccessor->isWritable($model, $mappedName)) {
                $field = Type::getNamedType($fieldObject->getType());

                if ($field instanceof InputObjectType) {
                    $resultValue = $this->hydrateInputType($field, $inputValues[$fieldName]);
                } else {
                    $resultValue = $inputValues[$fieldName];
                }

                $this->propertyAccessor->setValue($model, $mappedName, $resultValue);
            }
        }

        return $model;
    }

    public function readAnnotationMapping(ReflectionClass $reflectionClass): array
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
     *
     *
     * @param mixed $value
     * @param $fieldName
     * @param $reflectionClass
     * @param ResolveInfo $info
     * @return mixed
     */
    private function convertValue($value, $fieldName, $reflectionClass, ResolveInfo $info)
    {
        try {
            $value = $info->schema->getType('Address');
        } catch (InvariantViolation $e) {
            $somethingWrong = $e;
        }

        return $value;
    }

//    private function readAnnotation(object $model, string $property): ?object
//    {
//        // TODO(murtukov): optimize this line
//        $reflectionClass = new \ReflectionClass($model);
//
//        $reader = AnnotationParser::getAnnotationReader();
//
//        return $reader->getPropertyAnnotation(
//            $reflectionClass->getProperty($property),
//            Field::class
//        );
//    }
}
