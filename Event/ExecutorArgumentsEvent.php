<?php

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Type\Schema;
use Symfony\Component\EventDispatcher\Event;

final class ExecutorArgumentsEvent extends Event
{
    /** @var Schema */
    private $schema;

    /** @var string */
    private $requestString;

    /** @var mixed */
    private $rootValue;

    /** @var \ArrayObject */
    private $contextValue;

    /** @var null|array */
    private $variableValue;

    /** @var null|string */
    private $operationName;

    public static function create(
        Schema $schema,
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
     * @param null|string $operationName
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

    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return Schema
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
     * @return null|string
     */
    public function getOperationName()
    {
        return $this->operationName;
    }
}
