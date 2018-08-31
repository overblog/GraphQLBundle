<?php

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\ClientAware;
use GraphQL\Error\Debug;
use GraphQL\Error\Error as GraphQLError;
use GraphQL\Error\FormattedError;
use GraphQL\Error\UserError as GraphQLUserError;
use GraphQL\Executor\ExecutionResult;
use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;
use Overblog\GraphQLBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ErrorHandler
{
    const DEFAULT_ERROR_MESSAGE = 'Internal server Error';

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var string */
    private $internalErrorMessage;

    /** @var array */
    private $exceptionMap;

    /** @var bool */
    private $mapExceptionsToParent;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        $internalErrorMessage = null,
        array $exceptionMap = [],
        $mapExceptionsToParent = false
    ) {
        $this->dispatcher = $dispatcher;
        if (empty($internalErrorMessage)) {
            $internalErrorMessage = self::DEFAULT_ERROR_MESSAGE;
        }
        $this->internalErrorMessage = $internalErrorMessage;
        $this->exceptionMap = $exceptionMap;
        $this->mapExceptionsToParent = $mapExceptionsToParent;
    }

    public function handleErrors(ExecutionResult $executionResult, $throwRawException = false, $debug = false)
    {
        $errorFormatter = $this->createErrorFormatter($debug);
        $executionResult->setErrorFormatter($errorFormatter);
        $exceptions = $this->treatExceptions($executionResult->errors, $throwRawException);
        $executionResult->errors = $exceptions['errors'];
        if (!empty($exceptions['extensions']['warnings'])) {
            $executionResult->extensions['warnings'] = \array_map($errorFormatter, $exceptions['extensions']['warnings']);
        }
    }

    private function createErrorFormatter($debug = false)
    {
        $debugMode = false;
        if ($debug) {
            $debugMode = Debug::INCLUDE_TRACE | Debug::INCLUDE_DEBUG_MESSAGE;
        }

        return function (GraphQLError $error) use ($debugMode) {
            $event = new ErrorFormattingEvent($error, FormattedError::createFromException($error, $debugMode, $this->internalErrorMessage));
            $this->dispatcher->dispatch(Events::ERROR_FORMATTING, $event);

            return $event->getFormattedError()->getArrayCopy();
        };
    }

    /**
     * @param GraphQLError[] $errors
     * @param bool           $throwRawException
     *
     * @return array
     *
     * @throws \Error|\Exception
     */
    private function treatExceptions(array $errors, $throwRawException)
    {
        $treatedExceptions = [
            'errors' => [],
            'extensions' => [
                'warnings' => [],
            ],
        ];

        /** @var GraphQLError $error */
        foreach ($this->flattenErrors($errors) as $error) {
            $rawException = $this->convertException($error->getPrevious());

            // raw GraphQL Error or InvariantViolation exception
            if (null === $rawException) {
                $treatedExceptions['errors'][] = $error;
                continue;
            }

            // recreate a error with converted exception
            $errorWithConvertedException = new GraphQLError(
                $error->getMessage(),
                $error->nodes,
                $error->getSource(),
                $error->getPositions(),
                $error->path,
                $rawException
            );

            // user error
            if ($rawException instanceof GraphQLUserError) {
                $treatedExceptions['errors'][] = $errorWithConvertedException;
                continue;
            }

            // user warning
            if ($rawException instanceof UserWarning) {
                $treatedExceptions['extensions']['warnings'][] = $errorWithConvertedException;
                continue;
            }

            // if is a catch exception wrapped in Error
            if ($throwRawException) {
                throw $rawException;
            }

            $treatedExceptions['errors'][] = $errorWithConvertedException;
        }

        return $treatedExceptions;
    }

    /**
     * @param GraphQLError[] $errors
     *
     * @return GraphQLError[]
     */
    private function flattenErrors(array $errors)
    {
        $flattenErrors = [];

        foreach ($errors as $error) {
            $rawException = $error->getPrevious();
            // multiple errors
            if ($rawException instanceof UserErrors) {
                $rawExceptions = $rawException;
                foreach ($rawExceptions->getErrors() as $rawException) {
                    $flattenErrors[] = GraphQLError::createLocatedError($rawException, $error->nodes, $error->path);
                }
            } else {
                $flattenErrors[] = $error;
            }
        }

        return $flattenErrors;
    }

    /**
     * Tries to convert a raw exception into a user warning or error
     * that is displayed to the user.
     *
     * @param \Exception|\Error $rawException
     *
     * @return \Exception|\Error
     */
    private function convertException($rawException = null)
    {
        if (null === $rawException || $rawException instanceof ClientAware) {
            return $rawException;
        }

        $errorClass = $this->findErrorClass($rawException);
        if (null !== $errorClass) {
            return new $errorClass($rawException->getMessage(), $rawException->getCode(), $rawException);
        }

        return $rawException;
    }

    /**
     * @param \Exception|\Error $rawException
     *
     * @return string|null
     */
    private function findErrorClass($rawException)
    {
        $rawExceptionClass = \get_class($rawException);
        if (isset($this->exceptionMap[$rawExceptionClass])) {
            return $this->exceptionMap[$rawExceptionClass];
        }

        if ($this->mapExceptionsToParent) {
            return $this->findErrorClassUsingParentException($rawException);
        }

        return null;
    }

    /**
     * @param \Exception|\Error $rawException
     *
     * @return string|null
     */
    private function findErrorClassUsingParentException($rawException)
    {
        foreach ($this->exceptionMap as $rawExceptionClass => $errorClass) {
            if ($rawException instanceof $rawExceptionClass) {
                return $errorClass;
            }
        }

        return null;
    }
}
