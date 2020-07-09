<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use function is_object;
use function is_string;
use function sprintf;

class UserErrors extends RuntimeException
{
    /** @var UserError[] */
    private array $errors = [];

    public function __construct(array $errors, $message = '', $code = 0, Exception $previous = null)
    {
        $this->setErrors($errors);
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param UserError[]|string[] $errors
     */
    public function setErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
    }

    /**
     * @param string|\GraphQL\Error\UserError $error
     */
    public function addError($error): self
    {
        if (is_string($error)) {
            $error = new UserError($error);
        } elseif (!is_object($error) || !$error instanceof \GraphQL\Error\UserError) {
            throw new InvalidArgumentException(sprintf('Error must be string or instance of %s.', \GraphQL\Error\UserError::class));
        }

        $this->errors[] = $error;

        return $this;
    }

    /**
     * @return UserError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
