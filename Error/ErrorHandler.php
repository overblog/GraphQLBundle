<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error;
use GraphQL\Executor\ExecutionResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LogLevel;

class ErrorHandler
{
    const DEFAULT_ERROR_MESSAGE = 'Internal server Error';
    const DEFAULT_USER_WARNING_CLASS = 'Overblog\\GraphQLBundle\\Error\\UserWarning';
    const DEFAULT_USER_ERROR_CLASS = 'Overblog\\GraphQLBundle\\Error\\UserError';

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

    public function __construct($internalErrorMessage = null, LoggerInterface $logger = null, array $exceptionMap = [])
    {
        $this->logger = (null === $logger) ? new NullLogger() : $logger;
        if (empty($internalErrorMessage)) {
            $internalErrorMessage = self::DEFAULT_ERROR_MESSAGE;
        }
        $this->internalErrorMessage = $internalErrorMessage;
        $this->exceptionMap = $exceptionMap;
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
     * @param Error[] $errors
     * @param bool    $throwRawException
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

        /** @var Error $error */
        foreach ($errors as $error) {
            $rawException = $this->convertException($error->getPrevious());

            // Parse error or user error
            if (null === $rawException) {
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
                    $treatedExceptions['errors'][] = Error::createLocatedError($rawException, $error->nodes);
                }
                continue;
            }

            // if is a try catch exception wrapped in Error
            if ($throwRawException) {
                throw $rawException;
            }

            $this->logException($rawException, LogLevel::CRITICAL);

            $treatedExceptions['errors'][] = new Error(
                $this->internalErrorMessage,
                $error->nodes,
                $rawException,
                $error->getSource(),
                $error->getPositions()
            );
        }

        return $treatedExceptions;
    }

    public function logException(\Exception $exception, $errorLevel = LogLevel::ERROR)
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
        $exceptions = $this->treatExceptions($executionResult->errors, $throwRawException);
        $executionResult->errors = $exceptions['errors'];
        if (!empty($exceptions['extensions']['warnings'])) {
            $executionResult->extensions['warnings'] = array_map(['GraphQL\Error', 'formatError'], $exceptions['extensions']['warnings']);
        }
    }

    /**
     * Tries to convert a raw exception into a user warning or error
     * that is displayed to the user.
     *
     * @param \Exception $rawException
     *
     * @return \Exception
     */
    protected function convertException(\Exception $rawException = null)
    {
        if (null === $rawException) {
            return;
        }

        if (!empty($this->exceptionMap[get_class($rawException)])) {
            $errorClass = $this->exceptionMap[get_class($rawException)];

            return new $errorClass($rawException->getMessage(), $rawException->getCode(), $rawException);
        }

        return $rawException;
    }
}
