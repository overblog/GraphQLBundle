<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ResolveErrors
{
    private ?ConstraintViolationListInterface $validationErrors = null;

    public function setValidationErrors(ConstraintViolationListInterface $errors): void
    {
        $this->validationErrors = $errors;
    }

    /**
     * Returns a collection of validation violations or null.
     */
    public function getValidationErrors(): ?ConstraintViolationListInterface
    {
        return $this->validationErrors;
    }
}
