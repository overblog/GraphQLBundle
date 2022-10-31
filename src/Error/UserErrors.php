<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use Exception;
use GraphQL\Error\UserError as GraphQLUserError;
use InvalidArgumentException;
use RuntimeException;

use function is_object;
use function is_string;
use function sprintf;

final class UserErrors extends RuntimeException
{
    /** @var GraphQLUserError[] */
    private array $errors = [];

    public function __construct(
        array $errors,
        string $message = '',
        int $code = 0,
        Exception $previous = null
    ) {
        $this->setErrors($errors);
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param GraphQLUserError[]|string[] $errors
     */
    public function setErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
    }

    /**
     * @param GraphQLUserError|string $error
     */
    public function addError($error): self
    {
        if (is_string($error)) {
            $error = new UserError($error);
        } elseif (!is_object($error) || !$error instanceof GraphQLUserError) {
            throw new InvalidArgumentException(sprintf('Error must be string or instance of %s.', GraphQLUserError::class));
        }

        $this->errors[] = $error;

        return $this;
    }

    /**
     * @return GraphQLUserError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
