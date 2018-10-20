<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Transformer;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InputBuilder
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var array
     */
    protected $classesMap;

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    public function __construct(ValidatorInterface $validator, $classesMap = [])
    {
        $this->validator = $validator;
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->classesMap = $classesMap;
    }

    /**
     * Get the PHP class for a given type.
     *
     * @param string $type
     *
     * @return object|false
     */
    private function getTypeClassInstance(string $type)
    {
        $classname = isset($this->classesMap[$type]) ? $this->classesMap[$type]['class'] : false;

        return $classname ? new $classname() : false;
    }

    /**
     * Extract given type from Resolve Info.
     *
     * @param string      $type
     * @param ResolveInfo $info
     *
     * @return Type
     */
    private function getType(string $type, ResolveInfo $info): Type
    {
        return $info->schema->getType($type);
    }

    /**
     * Populate an object based on type with given data.
     *
     * @param Type        $type
     * @param mixed       $data
     * @param bool        $multiple
     * @param ResolveInfo $info
     *
     * @return mixed
     */
    private function populateObject(Type $type, $data, $multiple = false, ResolveInfo $info)
    {
        if ($multiple) {
            return \array_map(function ($data) use ($type, $info) {
                return $this->populateObject($type, $data, false, $info);
            }, $data);
        }

        if ($type instanceof EnumType) {
            $instance = $this->getTypeClassInstance($type->name);
            if ($instance) {
                $this->accessor->setValue($instance, 'value', $data);

                return $instance;
            } else {
                return $data;
            }
        } elseif ($type instanceof InputType) {
            $instance = $this->getTypeClassInstance($type->name);
            if (!$instance) {
                return $data;
            }

            $fields = $type->getFields();

            foreach ($fields as $name => $field) {
                $fieldData = $this->accessor->getValue($data, \sprintf('[%s]', $name));

                if ($field->getType() instanceof ListOfType) {
                    $fieldValue = $this->populateObject($field->getType()->getWrappedType(), $fieldData, true, $info);
                } else {
                    $fieldValue = $this->populateObject($field->getType(), $fieldData, false, $info);
                }

                $this->accessor->setValue($instance, $name, $fieldValue);
            }

            return $instance;
        } else {
            return $data;
        }
    }

    /**
     * Given a GraphQL type and an array of data, populate corresponding object recursively
     * using annoted classes.
     *
     * @param string      $argType
     * @param mixed       $data
     * @param ResolveInfo $info
     *
     * @return mixed
     */
    public function getInstanceAndValidate(string $argType, $data, ResolveInfo $info)
    {
        $isRequired = '!' === $argType[\strlen($argType) - 1];
        $isMultiple = '[' === $argType[0];
        $endIndex = ($isRequired ? 1 : 0) + ($isMultiple ? 1 : 0);
        $type = \substr($argType, $isMultiple ? 1 : 0, $endIndex > 0 ? -$endIndex : \strlen($argType));

        $result = $this->populateObject($this->getType($type, $info), $data, $isMultiple, $info);

        $errors = $this->validator->validate($result);
        if (\count($errors) > 0) {
            throw new \Exception((string) $errors);
        } else {
            return $result;
        }
    }
}
