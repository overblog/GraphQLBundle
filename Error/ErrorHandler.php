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

    public function __construct($internalErrorMessage = null, LoggerInterface $logger = null)
    {
        $this->logger = (null === $logger) ? new NullLogger() : $logger;
        if (empty($internalErrorMessage)) {
            $internalErrorMessage = self::DEFAULT_ERROR_MESSAGE;
        }
        $this->internalErrorMessage = $internalErrorMessage;
    }

    /**
     * @param Error[] $errors
     * @param $throwRawException
     *
     * @return Error[]
     *
     * @throws \Exception
     */
    protected function treatErrors(array $errors, $throwRawException)
    {
        $treatedErrors = [];

        /** @var Error $error */
        foreach ($errors as $error) {
            $rawException = $error->getPrevious();

            // Parse error or user error
            if (null === $rawException || $rawException instanceof UserError) {
                $treatedErrors[] = $error;
                continue;
            }

            // multiple errors
            if ($rawException instanceof UserErrors) {
                $rawExceptions = $rawException;
                foreach ($rawExceptions->getErrors() as $rawException) {
                    $treatedErrors[] = Error::createLocatedError($rawException, $error->nodes);
                }
                continue;
            }

            // if is a try catch exception wrapped in Error
            if ($throwRawException) {
                throw $rawException;
            }

            $this->logException($rawException);

            $treatedErrors[] = new Error(
                $this->internalErrorMessage,
                $error->nodes,
                $rawException,
                $error->getSource(),
                $error->getPositions()
            );
        }

        return $treatedErrors;
    }

    public function logException(\Exception $exception)
    {
        $message = sprintf(
            '%s: %s[%d] (uncaught exception) at %s line %s.',
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
        $executionResult->errors = $this->treatErrors($executionResult->errors, $throwRawException);
    }
}
