<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Hydrator\Annotation\Field;
use Overblog\GraphQLBundle\Hydrator\Converters\ConverterAnnotationInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class Hydrator
{
    private static AnnotationReader $annotationReader;
    private PropertyAccessorInterface $propertyAccessor;
    private ServiceLocator $converters;

    public function __construct(PropertyAccessorInterface $propertyAccessor, ServiceLocator $converters)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->converters = $converters;
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
        if (empty($inputType->config['model'])) {
            return $inputValues;
        }

        $model = new $inputType->config['model'];
        $reflectionClass = new ReflectionClass($model);
        $annotationMapping = $this->readAnnotationMapping($reflectionClass);
        $fields = $inputType->getFields();

        foreach ($inputValues as $fieldName => $fieldValue) {
            if (empty($fields[$fieldName])) {
                continue;
            }

            $fieldObject = $fields[$fieldName];
            $targetName = $annotationMapping[$fieldName] ?? $fieldName;

            if ($this->propertyAccessor->isWritable($model, $targetName)) {
                $type = $fieldObject->getType();

                if (Type::getNullableType($type) instanceof ListOfType) {
                    $resultValue = $this->hydrateCollectionValue($fieldObject, $fieldValue);
                } else {
                    $resultValue = $this->hydrateValue($fieldObject, $fieldValue);
                }

                $this->propertyAccessor->setValue(
                    $model,
                    $targetName,
                    $this->convertValue($resultValue, $model, $targetName)
                );
            }
        }

        return $model;
    }

    private function hydrateValue($fieldObject, $fieldValue)
    {
        $field = Type::getNamedType($fieldObject->getType());

        if ($field instanceof InputObjectType) {
            $fieldValue = $this->hydrateInputType($field, $fieldValue);
        }

        return $fieldValue;
    }

    private function hydrateCollectionValue($fieldObject, $fieldValue)
    {
        $result = [];

        foreach ($fieldValue as $value) {
            $result[] = $this->hydrateValue($fieldObject, $value);
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return mixed
     * @throws ReflectionException
     */
    private function convertValue($value, object $model, string $targetName)
    {
        $reflectionClass = new ReflectionClass($model);
        $property = $reflectionClass->getProperty($targetName);

        $reader = self::getAnnotationReader();

        /** @var ConverterAnnotationInterface $annotation */
        $annotation = $reader->getPropertyAnnotation($property, ConverterAnnotationInterface::class);

        if (null !== $annotation) {
            $converter = $this->converters->get($annotation::getConverterClass());
        }

        return $value;
    }

    public static function getAnnotationReader(): AnnotationReader
    {
        if (!isset(self::$annotationReader)) {
            if (!class_exists(AnnotationReader::class) || !class_exists(AnnotationRegistry::class)) {
                throw new RuntimeException('In order to use graphql annotation, you need to require doctrine annotations');
            }

            AnnotationRegistry::registerLoader('class_exists');
            self::$annotationReader = new AnnotationReader();
        }

        return self::$annotationReader;
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
}
