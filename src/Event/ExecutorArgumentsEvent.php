<?php

namespace Overblog\GraphQLBundle\Event;

use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Symfony\Component\EventDispatcher\Event;

final class ExecutorArgumentsEvent extends Event
{
    /** @var ExtensibleSchema */
    private $schema;

    /** @var string */
    private $requestString;

    /** @var mixed */
    private $rootValue;

    /** @var \ArrayObject */
    private $contextValue;

    /** @var array|null */
    private $variableValue;

    /** @var string|null */
    private $operationName;

    public static function create(
        ExtensibleSchema $schema,
        $requestString,
        \ArrayObject $contextValue,
        $rootValue = null,
        array $variableValue = null,
        $operationName = null
    ) {
        $instance = new static();
        $instance->setSchema($schema);
        $instance->setRequestString($requestString);
        $instance->setContextValue($contextValue);
        $instance->setRootValue($rootValue);
        $instance->setVariableValue($variableValue);
        $instance->setOperationName($operationName);

        return $instance;
    }

    /**
     * @param string|null $operationName
     */
    public function setOperationName($operationName = null)
    {
        $this->operationName = $operationName;
    }

    public function setContextValue(\ArrayObject $contextValue = null)
    {
        $this->contextValue = $contextValue;
    }

    /**
     * @param mixed $rootValue
     */
    public function setRootValue($rootValue = null)
    {
        $this->rootValue = $rootValue;
    }

    /**
     * @param string $requestString
     */
    public function setRequestString($requestString)
    {
        $this->requestString = $requestString;
    }

    public function setVariableValue(array $variableValue = null)
    {
        $this->variableValue = $variableValue;
    }

    public function setSchema(ExtensibleSchema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return ExtensibleSchema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return string
     */
    public function getRequestString()
    {
        return $this->requestString;
    }

    /**
     * @return array|null
     */
    public function getRootValue()
    {
        return $this->rootValue;
    }

    /**
     * @return \ArrayObject
     */
    public function getContextValue()
    {
        return $this->contextValue;
    }

    /**
     * @return array|null
     */
    public function getVariableValue()
    {
        return $this->variableValue;
    }

    /**
     * @return string|null
     */
    public function getOperationName()
    {
        return $this->operationName;
    }
}
