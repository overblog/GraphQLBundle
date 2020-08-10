<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Hydrator;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Config\Parser\AnnotationParser;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Hydrator\Annotation\Field;
use Overblog\GraphQLBundle\Hydrator\Annotation\Model;
use Overblog\GraphQLBundle\Hydrator\Converters\ConverterAnnotationInterface;
use Overblog\GraphQLBundle\Hydrator\Converters\Entity;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class Hydrator
{
    private const ENTITY_ANNOTATIONS = [
        ORM\OneToOne::class,
        ORM\OneToMany::class,
        ORM\ManyToOne::class,
        ORM\ManyToMany::class
    ];

    private static array $annotationCache = [];

    private AnnotationReader $annotationReader;
    private PropertyAccessorInterface $propertyAccessor;
    private EntityManagerInterface $em;
    private ServiceLocator $converters;
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
     * @throws ORM\MappingException|NonUniqueResultException|ReflectionException|Exception
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
     *
     * @return object
     *
     * @throws ORM\MappingException|ReflectionException|NonUniqueResultException
     */
    private function hydrateInputType(InputObjectType $inputType, $inputValues, object $model = null): object
    {
        if (empty($inputType->config['model'])) {
            return $inputValues;
        }

        $modelName = null !== $model ? get_class($model) : $inputType->config['model'];
        $modelReflection = new ReflectionClass($modelName);

        $entityAnnotation = $this->annotationReader->getClassAnnotation($modelReflection, Orm\Entity::class);

        if (null === $model) {
            if (null !== $entityAnnotation) {
                $model = $this->getEntityModel($modelName, $inputValues);
            } else {
                $model = new $modelName();
            }
        }

        $annotationMapping = $this->readAnnotationMapping($modelReflection);
        $fields = $inputType->getFields();

        foreach ($inputValues as $fieldName => $fieldValue) {
            if (empty($fields[$fieldName])) {
                continue;
            }

            $fieldObject = $fields[$fieldName];
            $targetField = $annotationMapping[$fieldName] ?? $fieldName;

            if ($this->propertyAccessor->isWritable($model, $targetField)) {

                $resultValue = $this->resolveConverter(
                   $modelName,
                   $targetField,
                   $fieldValue,
                   $modelReflection,
                   $fieldObject
               );

                $this->propertyAccessor->setValue(
                    $model,
                    $targetField,
                    $resultValue
                );
            }
        }

        return $model;
    }

    public function resolveConverter($modelName, $targetField, $fieldValue, $modelReflection, $fieldObject)
    {
        # 1. Check if converter declared explicitely -----------------------------------------------------------
        $converterAnnotation = $this->getPropertyAnnotation($modelName, $targetField, ConverterAnnotationInterface::class);
        if (null !== $converterAnnotation) {
            $converter = $this->converters->get($converterAnnotation::getConverterClass());
            $resultValue = $converter->convert($fieldValue, $converterAnnotation);
        }

        # ------------------------------------------------------------------------------------------------------

        # 2. Check if an entity converter can be applied automatically (single or collection)
        // Check if target property has a type-hint which is en Entity itself
        $typeHint = $modelReflection->getProperty($targetField)->getType();
        if (null !== $typeHint && class_exists((string) $typeHint)) {
            $typeAnno = $this->annotationReader->getClassAnnotation(new ReflectionClass($typeHint), ORM\Entity::class);
            if (null !== $typeAnno) {
                // use entity converter
                $converter = $this->converters->get(Converters\Entity::class);
                $a = new Converters\Entity;
                $a->value = (string) $typeHint;
                $resultValue = $converter->convert($fieldValue, $a);
            }
        }

        // Check if target property has a Doctrine annotation declared on it
        foreach (self::ENTITY_ANNOTATIONS as $annotationName) {
            /** @var ORM\OneToOne|ORM\OneToMany|ORM\ManyToOne|ORM\ManyToMany $a */
            $columnAnnotation = $this->getPropertyAnnotation($modelName, $targetField, $annotationName);

            if (null !== $columnAnnotation) {
                if (strpos($columnAnnotation->targetEntity, '\\') === false) {
                    // Fix namespace
                    $columnAnnotation->targetEntity = $modelReflection->getNamespaceName()."\\$columnAnnotation->targetEntity";
                }

                switch (true) {
                    case $columnAnnotation instanceof ORM\OneToOne:
                    case $columnAnnotation instanceof ORM\ManyToOne:
                        $converter = $this->converters->get($columnAnnotation->targetEntity);
                        $entity = new Entity();
                        $entity->value = "";
                        $entity->isCollection = true;
                        $resultValue = $converter->convert($fieldValue, $entity);
                        break;

                    case $columnAnnotation instanceof ORM\OneToMany:
                    case $columnAnnotation instanceof ORM\ManyToMany:
                        $converter = $this->converters->get($columnAnnotation->targetEntity);
                        $entity = new Entity();
                        $entity->value = "";
                        $entity->isCollection = true;
                        $resultValue = $converter->convert($fieldValue, $columnAnnotation);
                        break;
                }

                break;
            }
        }

        # ------------------------------------------------------------------------------------------------------

        # 3. Use default converter (single or collection)
        if (Type::getNullableType($fieldObject->getType()) instanceof ListOfType) {
            $resultValue = $this->hydrateCollectionValue($fieldObject, $fieldValue, $modelName);
        } else {
            $resultValue = $this->hydrateValue($fieldObject, $fieldValue, $resultValue);
        }

        return $resultValue;
    }

    /**
     * Returns property annotation from cache.
     *
     * @throws ReflectionException
     */
    private function getPropertyAnnotation(string $className, string $propertyName, string $annotationName): ?object
    {
        self::$annotationCache[$className][$propertyName] ??= $this->annotationReader->getPropertyAnnotations(new ReflectionProperty($className, $propertyName));

        foreach (self::$annotationCache[$className][$propertyName] as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * Returns class annotation from cache.
     *
     * @throws ReflectionException
     */
    private function getClassAnnotation(string $className, string $annotationName): ?object
    {
        static $cache = [];

        return $cache[$className][$annotationName] ??=
            $this->annotationReader->getClassAnnotation(new ReflectionClass($className), $annotationName);
    }

    /**
     * @param string $modelName
     * @param array $inputValues
     * @return int|mixed|string|null
     *
     * @throws ORM\MappingException|NonUniqueResultException|ReflectionException
     */
    private function getEntityModel(string $modelName, array $inputValues)
    {
        $idValue = $this->resolveIdValue($modelName, $inputValues);

        if (null === $idValue) {
            return new $modelName();
        }

        // entity
        $meta = $this->em->getClassMetadata($modelName);
        $entityIdField = $meta->getSingleIdentifierFieldName();

        $builder = $this->em->createQueryBuilder()
            ->select('o')
            ->from($modelName, 'o')
            ->where("o.$entityIdField = :identifier")
            ->setParameter('identifier', $idValue);

        $result = $builder->getQuery()->getOneOrNullResult();

        if (null === $result) {
            throw new Exception("Couldn't find entity");
        }

        return $result;
    }

    /**
     * @return array|mixed|null
     * @throws ReflectionException
     */
    private function resolveIdValue(string $modelName, array $inputValues)
    {
        $reflectionClass = new ReflectionClass($modelName);
        $modelAnnotation = $this->annotationReader->getClassAnnotation($reflectionClass, Model::class);

        $identifier = $modelAnnotation->identifier ?? 'id';
        $path = explode('.', $identifier);

        // If a path is provided, search the value from top argument down
        if (count($path) > 1) {
            $temp = &$this->args;
            foreach($path as $key) {
                $temp = &$temp[$key];
            }
            return $temp;
        }

        return $inputValues[$identifier] ?? $this->args[$identifier] ?? null;
    }

    /**
     * @param $fieldObject
     * @param $fieldValue
     * @return mixed
     * @throws ReflectionException
     */
    private function hydrateValue($fieldObject, $fieldValue, object $model = null)
    {
        $field = Type::getNamedType($fieldObject->getType());

        if ($field instanceof InputObjectType) {
            $fieldValue = $this->hydrateInputType($field, $fieldValue, $model);
        }

        return $fieldValue;
    }

    /**
     * @param $fieldObject
     * @param $fieldValue
     * @param string $modelName
     * @return array
     * @throws ORM\MappingException
     * @throws ReflectionException
     */
    private function hydrateCollectionValue($fieldObject, $fieldValue, string $modelName)
    {
        $isBuiltInTypes = Type::isBuiltInType(Type::getNamedType($fieldObject->getType()));

        if (true === $isBuiltInTypes) {
            $meta = $this->em->getClassMetadata($modelName);
            $entityIdField = $meta->getSingleIdentifierFieldName();

            $query = $this->em->createQuery(<<<DQL
            SELECT o FROM $modelName o
            WHERE o.$entityIdField IN (:ids)
            INDEX BY o.$entityIdField
            DQL);

            $query->setParameter('ids', $fieldValue);
            $entities = $query->getResult();

            if (count($entities) !== count($fieldValue)) {
                throw new Exception("Couldn't find all entities.");
            }
        }

        $result = [];
        foreach ($fieldValue as $value) {
            $result[] = $this->hydrateValue($fieldObject, $value, $entities[$value] ?? null);
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
