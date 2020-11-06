<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Exception;

use Exception;
use GraphQL\Error\ClientAware;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

class ArgumentsValidationException extends Exception implements ClientAware
{
    private ConstraintViolationListInterface $violations;

    public function __construct(ConstraintViolationListInterface $violations, Throwable $previous = null)
    {
        $this->violations = $violations;
        parent::__construct('validation', 0, $previous);
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'arguments_validation_error';
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
