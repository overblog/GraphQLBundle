<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Transformer;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Overblog\GraphQLBundle\Error\InvalidArgumentError;
use Overblog\GraphQLBundle\Error\InvalidArgumentsError;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArgumentsTransformer
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

    public function __construct(ValidatorInterface $validator = null, $classesMap = [])
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
    private function populateObject(Type $type, $data, bool $multiple, ResolveInfo $info)
    {
        if (null === $data) {
            return $data;
        }

        if ($type instanceof NonNull) {
            $type = $type->getWrappedType();
        }

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
        } elseif ($type instanceof InputObjectType) {
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
    public function getInstanceAndValidate(string $argType, $data, ResolveInfo $info, string $argName)
    {
        $isRequired = '!' === $argType[\strlen($argType) - 1];
        $isMultiple = '[' === $argType[0];
        $endIndex = ($isRequired ? 1 : 0) + ($isMultiple ? 1 : 0);
        $type = \substr($argType, $isMultiple ? 1 : 0, $endIndex > 0 ? -$endIndex : \strlen($argType));

        $result = $this->populateObject($this->getType($type, $info), $data, $isMultiple, $info);
        $errors = [];
        if (\is_object($result) && $this->validator) {
            $errors = $this->validator->validate($result);
        }

        if (\count($errors) > 0) {
            throw new InvalidArgumentError($argName, $errors);
        } else {
            return $result;
        }
    }

    /**
     * Transform a list of arguments into their corresponding php class and validate them.
     *
     * @param array       $mapping
     * @param mixed       $data
     * @param ResolveInfo $info
     *
     * @return array
     */
    public function getArguments(array $mapping, $data, ResolveInfo $info)
    {
        $args = [];
        $exceptions = [];

        foreach ($mapping as $name => $type) {
            try {
                $value = $this->getInstanceAndValidate($type, $data[$name], $info, $name);
                $args[] = $value;
            } catch (InvalidArgumentError $exception) {
                $exceptions[] = $exception;
            }
        }

        if (!empty($exceptions)) {
            throw new InvalidArgumentsError($exceptions);
        }

        return $args;
    }
}
