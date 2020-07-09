<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Event;

use ArrayObject;
use Overblog\GraphQLBundle\Definition\Type\ExtensibleSchema;
use Symfony\Contracts\EventDispatcher\Event;
use function microtime;

final class ExecutorArgumentsEvent extends Event
{
    private ExtensibleSchema $schema;
    private string $requestString;
    private ArrayObject $contextValue;
    private ?array $variableValue = null;
    private ?string $operationName = null;
    private ?float $startTime = null;

    /** @var mixed */
    private $rootValue;

    /**
     * @param mixed|null $rootValue
     *
     * @return static
     */
    public static function create(
        ExtensibleSchema $schema,
        string $requestString,
        ArrayObject $contextValue,
        $rootValue = null,
        array $variableValue = null,
        string $operationName = null
    ): self {
        $instance = new static();
        $instance->setSchema($schema);
        $instance->setRequestString($requestString);
        $instance->setContextValue($contextValue);
        $instance->setRootValue($rootValue);
        $instance->setVariableValue($variableValue);
        $instance->setOperationName($operationName);
        $instance->setStartTime(microtime(true));

        return $instance;
    }

    public function setOperationName(?string $operationName): void
    {
        $this->operationName = $operationName;
    }

    public function setContextValue(ArrayObject $contextValue): void
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

    public function setRequestString(string $requestString): void
    {
        $this->requestString = $requestString;
    }

    public function setVariableValue(?array $variableValue): void
    {
        $this->variableValue = $variableValue;
    }

    public function setSchema(ExtensibleSchema $schema): void
    {
        $this->schema = $schema;
    }

    public function setStartTime(float $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getSchema(): ExtensibleSchema
    {
        return $this->schema;
    }

    public function getRequestString(): string
    {
        return $this->requestString;
    }

    public function getRootValue(): ?array
    {
        return $this->rootValue;
    }

    public function getContextValue(): ArrayObject
    {
        return $this->contextValue;
    }

    public function getVariableValue(): ?array
    {
        return $this->variableValue;
    }

    public function getOperationName(): ?string
    {
        return $this->operationName;
    }

    public function getStartTime(): ?float
    {
        return $this->startTime;
    }
}
