<?php

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\Error as GraphQLError;
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

    /**
     * @param GraphQLError[] $errors
     * @param bool           $throwRawException
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function treatExceptions(array $errors, $throwRawException)
    {
        $treatedExceptions = [
            'errors' => [],
            'extensions' => [
                'warnings' => [],
            ],
        ];

        /** @var GraphQLError $error */
        foreach ($errors as $error) {
            $rawException = $this->convertException($error->getPrevious());

            // raw GraphQL Error or InvariantViolation exception
            if (null === $rawException || $rawException instanceof GraphQLUserError) {
                $treatedExceptions['errors'][] = $error;
                continue;
            }

            // user error
            if ($rawException instanceof $this->userErrorClass) {
                $treatedExceptions['errors'][] = $error;
                if ($rawException->getPrevious()) {
                    $this->logException($rawException->getPrevious());
                }
                continue;
            }

            // user warning
            if ($rawException instanceof $this->userWarningClass) {
                $treatedExceptions['extensions']['warnings'][] = $error;
                if ($rawException->getPrevious()) {
                    $this->logException($rawException->getPrevious(), LogLevel::WARNING);
                }
                continue;
            }

            // multiple errors
            if ($rawException instanceof UserErrors) {
                $rawExceptions = $rawException;
                foreach ($rawExceptions->getErrors() as $rawException) {
                    $treatedExceptions['errors'][] = GraphQLError::createLocatedError($rawException, $error->nodes);
                }
                continue;
            }

            // if is a try catch exception wrapped in Error
            if ($throwRawException) {
                throw $rawException;
            }

            $this->logException($rawException, LogLevel::CRITICAL);

            $treatedExceptions['errors'][] = new GraphQLError(
                $this->internalErrorMessage,
                $error->nodes,
                $error->getSource(),
                $error->getPositions(),
                $error->path,
                $rawException
            );
        }

        return $treatedExceptions;
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
        $executionResult->setErrorFormatter(sprintf('\%s::formatError', GraphQLError::class));
        $exceptions = $this->treatExceptions($executionResult->errors, $throwRawException);
        $executionResult->errors = $exceptions['errors'];
        if (!empty($exceptions['extensions']['warnings'])) {
            $executionResult->extensions['warnings'] = array_map([GraphQLError::class, 'formatError'], $exceptions['extensions']['warnings']);
        }
    }

    /**
     * Tries to convert a raw exception into a user warning or error
     * that is displayed to the user.
     *
     * @param \Exception|\Error $rawException
     *
     * @return \Exception|\Error
     */
    protected function convertException($rawException = null)
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
