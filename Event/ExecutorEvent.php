<?php

namespace Overblog\GraphQLBundle\Event;

use GraphQL\Type\Schema;
use Symfony\Component\EventDispatcher\Event;

final class ExecutorEvent extends Event
{
    /** @var Schema */
    private $schema;

    /** @var string */
    private $requestString;

    /** @var \ArrayObject */
    private $rootValue;

    /** @var \ArrayObject */
    private $contextValue;

    /** @var null|array */
    private $variableValue;

    /** @var null|string */
    private $operationName;

    public function __construct(Schema $schema, $requestString, \ArrayObject $rootValue, \ArrayObject $contextValue, $variableValue = null, $operationName = null)
    {
        $this->schema = $schema;
        $this->requestString = $requestString;
        $this->rootValue = $rootValue;
        $this->contextValue = $contextValue;
        $this->variableValue = $variableValue;
        $this->operationName = $operationName;
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
     * @return \ArrayObject
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
