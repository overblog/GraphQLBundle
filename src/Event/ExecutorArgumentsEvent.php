<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Symfony\Contracts\EventDispatcher\Event;

final class ExecutorArgumentsEvent extends Event
{
    /** @var string */
    private $schemaName;

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
            string $schemaName,
            ExtensibleSchema $schema,
            $requestString,
            \ArrayObject $contextValue,
            $rootValue = null,
            array $variableValue = null,
            $operationName = null
        ) {
        $instance = new static();
        $instance->setSchemaName($schemaName);
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
    public function setOperationName($operationName = null): void
    {
        $this->operationName = $operationName;
    }

    public function setContextValue(\ArrayObject $contextValue = null): void
    {
        $this->contextValue = $contextValue;
    }

    /**
     * @param mixed $rootValue
     */
    public function setRootValue($rootValue = null): void
    {
        $this->rootValue = $rootValue;
    }

    /**
     * @param string $requestString
     */
    public function setRequestString($requestString): void
    {
        $this->requestString = $requestString;
    }

    public function setVariableValue(array $variableValue = null): void
    {
        $this->variableValue = $variableValue;
    }

    /**
     * @return string
     */
    public function getSchemaName()
    {
        return $this->schemaName;
    }

    public function setSchemaName(string $schemaName)
    {
        $this->schemaName = $schemaName;
    }

    public function setSchema(ExtensibleSchema $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * @return ExtensibleSchema
     */
    public function getSchema(): ExtensibleSchema
    {
        return $this->schema;
    }

    /**
     * @return string
     */
    public function getRequestString(): string
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
    public function getContextValue(): \ArrayObject
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
