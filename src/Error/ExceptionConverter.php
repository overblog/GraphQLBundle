<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\ClientAware as ClientAwareInterface;
use Throwable;
use function get_class;

final class ExceptionConverter implements ExceptionConverterInterface
{
    /**
     * @var array<string, string>
     */
    private array $exceptionMap;

    private bool $mapExceptionsToParent;

    /**
     * @param array<string, string> $exceptionMap
     */
    public function __construct(array $exceptionMap, bool $mapExceptionsToParent = false)
    {
        $this->exceptionMap = $exceptionMap;
        $this->mapExceptionsToParent = $mapExceptionsToParent;
    }

    /**
     * {@inheritdoc}
     */
    public function convertException(Throwable $exception): Throwable
    {
        if ($exception instanceof ClientAwareInterface) {
            return $exception;
        }

        $errorClass = $this->findErrorClass($exception);

        if (null !== $errorClass) {
            return new $errorClass($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $exception;
    }

    private function findErrorClass(Throwable $exception): ?string
    {
        $exceptionClass = get_class($exception);

        if (isset($this->exceptionMap[$exceptionClass])) {
            return $this->exceptionMap[$exceptionClass];
        }

        if ($this->mapExceptionsToParent) {
            return $this->findErrorClassUsingParentException($exception);
        }

        return null;
    }

    private function findErrorClassUsingParentException(Throwable $exception): ?string
    {
        foreach ($this->exceptionMap as $exceptionClass => $errorExceptionClass) {
            if ($exception instanceof $exceptionClass) {
                return $errorExceptionClass;
            }
        }

        return null;
    }
}
