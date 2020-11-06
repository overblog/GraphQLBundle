<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use Closure;
use Error;
use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error as GraphQLError;
use GraphQL\Error\FormattedError;
use GraphQL\Error\UserError as GraphQLUserError;
use GraphQL\Executor\ExecutionResult;
use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;
use Overblog\GraphQLBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;

class ErrorHandler
{
    public const DEFAULT_ERROR_MESSAGE = 'Internal server Error';

    private EventDispatcherInterface $dispatcher;
    private ExceptionConverterInterface $exceptionConverter;
    private string $internalErrorMessage;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        ExceptionConverterInterface $exceptionConverter,
        string $internalErrorMessage = self::DEFAULT_ERROR_MESSAGE
    ) {
        $this->dispatcher = $dispatcher;
        $this->exceptionConverter = $exceptionConverter;
        $this->internalErrorMessage = $internalErrorMessage;
    }

    public function handleErrors(ExecutionResult $executionResult, bool $throwRawException = false, bool $debug = false): void
    {
        $errorFormatter = $this->createErrorFormatter($debug);

        $executionResult->setErrorFormatter($errorFormatter);

        $exceptions = $this->treatExceptions($executionResult->errors, $throwRawException);
        $executionResult->errors = $exceptions['errors'];

        if (!empty($exceptions['extensions']['warnings'])) {
            $executionResult->extensions['warnings'] = array_map($errorFormatter, $exceptions['extensions']['warnings']);
        }
    }

    private function createErrorFormatter(bool $debug = false): Closure
    {
        $debugMode = DebugFlag::NONE;

        if ($debug) {
            $debugMode = DebugFlag::INCLUDE_TRACE | DebugFlag::INCLUDE_DEBUG_MESSAGE;
        }

        return function (GraphQLError $error) use ($debugMode): array {
            $event = new ErrorFormattingEvent($error, FormattedError::createFromException($error, $debugMode, $this->internalErrorMessage));

            $this->dispatcher->dispatch($event, Events::ERROR_FORMATTING); // @phpstan-ignore-line

            return $event->getFormattedError()->getArrayCopy();
        };
    }

    /**
     * @param GraphQLError[] $errors
     *
     * @throws Error|Exception
     */
    private function treatExceptions(array $errors, bool $throwRawException): array
    {
        $treatedExceptions = [
            'errors' => [],
            'extensions' => [
                'warnings' => [],
            ],
        ];

        /** @var GraphQLError $error */
        foreach ($this->flattenErrors($errors) as $error) {
            $rawException = $error->getPrevious();

            if (null !== $rawException) {
                $rawException = $this->exceptionConverter->convertException($error->getPrevious());
            }

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
    private function flattenErrors(array $errors): array
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
}
