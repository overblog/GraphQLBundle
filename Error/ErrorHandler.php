<?php

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\Error as GraphQLError;
use GraphQL\Error\FormattedError;
use GraphQL\Error\UserError as GraphQLUserError;
use GraphQL\Executor\ExecutionResult;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class ErrorHandler
{
    const DEFAULT_ERROR_MESSAGE = 'Internal server Error';
    const DEFAULT_USER_WARNING_CLASS = UserWarning::class;
    const DEFAULT_USER_ERROR_CLASS = UserError::class;
    /** callable */
    const DEFAULT_ERROR_FORMATTER = [FormattedError::class, 'createFromException'];

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $internalErrorMessage;

    /** @var array */
    private $exceptionMap;

    /** @var string */
    private $userWarningClass = self::DEFAULT_USER_WARNING_CLASS;

    /** @var string */
    private $userErrorClass = self::DEFAULT_USER_ERROR_CLASS;

    /** @var callable|null */
    private $errorFormatter;

    /** @var bool */
    private $mapExceptionsToParent;

    public function __construct(
        $internalErrorMessage = null,
        LoggerInterface $logger = null,
        array $exceptionMap = [],
        $mapExceptionsToParent = false
    ) {
        $this->logger = (null === $logger) ? new NullLogger() : $logger;
        if (empty($internalErrorMessage)) {
            $internalErrorMessage = self::DEFAULT_ERROR_MESSAGE;
        }
        $this->internalErrorMessage = $internalErrorMessage;
        $this->exceptionMap = $exceptionMap;
        $this->mapExceptionsToParent = $mapExceptionsToParent;
    }

    public function setUserWarningClass($userWarningClass)
    {
        $this->userWarningClass = $userWarningClass;

        return $this;
    }

    public function setUserErrorClass($userErrorClass)
    {
        $this->userErrorClass = $userErrorClass;

        return $this;
    }

    public function setErrorFormatter(callable $errorFormatter = null)
    {
        $this->errorFormatter = $errorFormatter;

        return $this;
    }

    /**
     * @param \Exception|\Error $exception
     * @param string            $errorLevel
     */
    public function logException($exception, $errorLevel = LogLevel::ERROR)
    {
        $message = sprintf(
            '%s: %s[%d] (caught exception) at %s line %s.',
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine()
        );

        $this->logger->$errorLevel($message, ['exception' => $exception]);
    }

    public function handleErrors(ExecutionResult $executionResult, $throwRawException = false)
    {
        $errorFormatter = $this->errorFormatter ? $this->errorFormatter : self::DEFAULT_ERROR_FORMATTER;
        $executionResult->setErrorFormatter($errorFormatter);
        FormattedError::setInternalErrorMessage($this->internalErrorMessage);
        $exceptions = $this->treatExceptions($executionResult->errors, $throwRawException);
        $executionResult->errors = $exceptions['errors'];
        if (!empty($exceptions['extensions']['warnings'])) {
            $executionResult->extensions['warnings'] = array_map($errorFormatter, $exceptions['extensions']['warnings']);
        }
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
            if (null === $rawException || $rawException instanceof GraphQLUserError) {
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
            if ($rawException instanceof $this->userErrorClass) {
                $treatedExceptions['errors'][] = $errorWithConvertedException;
                if ($rawException->getPrevious()) {
                    $this->logException($rawException->getPrevious());
                }
                continue;
            }

            // user warning
            if ($rawException instanceof $this->userWarningClass) {
                $treatedExceptions['extensions']['warnings'][] = $errorWithConvertedException;
                if ($rawException->getPrevious()) {
                    $this->logException($rawException->getPrevious(), LogLevel::WARNING);
                }
                continue;
            }

            // if is a catch exception wrapped in Error
            if ($throwRawException) {
                throw $rawException;
            }

            $this->logException($rawException, LogLevel::CRITICAL);

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
        if (null === $rawException) {
            return;
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
        $rawExceptionClass = get_class($rawException);
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
