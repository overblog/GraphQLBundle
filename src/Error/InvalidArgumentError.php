<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use Exception;
use GraphQL\Error\UserError as GraphQLUserError;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidArgumentError extends GraphQLUserError
{
    private string $name;
    private ConstraintViolationListInterface $errors;

    public function __construct(string $name, ConstraintViolationListInterface $errors, $message = '', $code = 0, Exception $previous = null)
    {
        $this->name = $name;
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getErrors(): ConstraintViolationListInterface
    {
        return $this->errors;
    }
}
