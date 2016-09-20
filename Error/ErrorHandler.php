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

class ErrorHandler
{
    const DEFAULT_ERROR_MESSAGE = 'Internal server Error';

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $internalErrorMessage;

    /** @var array */
    private $exceptionMap;

    public function __construct($internalErrorMessage = null, LoggerInterface $logger = null, array $exceptionMap = [])
    {
        $this->logger = (null === $logger) ? new NullLogger() : $logger;
        if (empty($internalErrorMessage)) {
            $internalErrorMessage = self::DEFAULT_ERROR_MESSAGE;
        }
        $this->internalErrorMessage = $internalErrorMessage;
        $this->exceptionMap = $exceptionMap;
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

            if ($rawException instanceof UserError) {
                $treatedExceptions['errors'][] = $error;
                if ($rawException->getPrevious()) {
                    $this->logException($rawException->getPrevious());
                }
                continue;
            }

            // user warnings
            if ($rawException instanceof UserWarning) {
                $treatedExceptions['extensions']['warnings'][] = $error;
                if ($rawException->getPrevious()) {
                    $this->logException($rawException->getPrevious());
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

            $this->logException($rawException);

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

    public function logException(\Exception $exception)
    {
        $message = sprintf(
            '%s: %s[%d] (caught exception) at %s line %s.',
            get_class($exception),
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine()
        );

        $this->logger->error($message, ['exception' => $exception]);
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
     * @return \Exception
     */
    protected function convertException(\Exception $rawException = null)
    {
        if (empty($rawException)) {
            return $rawException;
        }

        $types = [
            'warnings' => 'Overblog\\GraphQLBundle\\Error\\UserWarning',
            'errors' => 'Overblog\\GraphQLBundle\\Error\\UserError',
        ];

        foreach ($types as $type => $errorClass) {
            if (!empty($this->exceptionMap[$type])
                && in_array(get_class($rawException), $this->exceptionMap[$type])) {
                return new $errorClass($rawException->getMessage(), $rawException->getCode(), $rawException);
            }
        }

        return $rawException;
    }
}
