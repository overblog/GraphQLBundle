<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
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
    private AnnotationReader $annotationReader;
    private PropertyAccessorInterface $propertyAccessor;
    private ServiceLocator $converters;
    private EntityManagerInterface $em;
    private array $args;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        ServiceLocator $converters,
        EntityManagerInterface $entityManager
    ) {
        if (!class_exists(AnnotationReader::class) || !class_exists(AnnotationRegistry::class)) {
            throw new RuntimeException('In order to use graphql annotation, you need to require doctrine annotations');
        }

        AnnotationRegistry::registerLoader('class_exists');

        $this->annotationReader = new AnnotationReader();
        $this->propertyAccessor = $propertyAccessor;
        $this->converters = $converters;
        $this->em = $entityManager;
    }

    /**
     * @throws ReflectionException
     */
    public function hydrate(ArgumentInterface $args, ResolveInfo $info): Models
    {
        $this->args = $args->getArrayCopy();
        $requestedField = $info->parentType->getField($info->fieldName);

        $models = new Models();

        foreach ($this->args as $argName => $input) {
            /** @var ListOfType|NonNull $argType */
            $argType = $requestedField->getArg($argName)->getType();

            /** @var InputObjectType $inputType */
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
    private function hydrateInputType(InputObjectType $inputType, $inputValues): object
    {
        if (empty($inputType->config['model'])) {
            return $inputValues;
        }

        // Check if target class is a Doctrine entity
        $modelName = $inputType->config['model'];
        $reflectionClass = new ReflectionClass($modelName);
        $entityAnnotation = $this->annotationReader->getClassAnnotation($reflectionClass, Entity::class);

        if (null !== $entityAnnotation) {
            $path = explode('.', $entityAnnotation->identifier);

            // If a path is provided, search the value from top argument down
            if (count($path) === 1) {
                $temp = &$this->args;
                foreach($path as $key) {
                    $temp = &$temp[$key];
                }
                $id = $temp;
            } elseif (isset($inputValues[$entityAnnotation->identifier])) {
                $id = $inputValues[$entityAnnotation->identifier];
            } elseif (isset($this->args[$entityAnnotation->identifier])) {
                $id = $this->args[$entityAnnotation->identifier];
            } else {
                $id = null;
            }

            // entity
            $entity = $this->em->find($modelName, $id);
        }

        $model = new $modelName();
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

    /**
     * @param $fieldObject
     * @param $fieldValue
     * @return mixed
     * @throws ReflectionException
     */
    private function hydrateValue($fieldObject, $fieldValue)
    {
        $field = Type::getNamedType($fieldObject->getType());

        if ($field instanceof InputObjectType) {
            $fieldValue = $this->hydrateInputType($field, $fieldValue);
        }

        return $fieldValue;
    }

    /**
     * @param $fieldObject
     * @param $fieldValue
     * @return array
     * @throws ReflectionException
     */
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

        /** @var ConverterAnnotationInterface $annotation */
        $annotation = $this->annotationReader->getPropertyAnnotation($property, ConverterAnnotationInterface::class);

        if (null !== $annotation) {
            $converter = $this->converters->get($annotation::getConverterClass());
            return $converter->convert($value, $annotation);
        }

        return $value;
    }

    private function readAnnotationMapping(ReflectionClass $reflectionClass): array
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
