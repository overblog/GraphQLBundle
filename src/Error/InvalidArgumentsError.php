<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\UserError;

/**
 * Class InvalidArgumentError.
 */
class InvalidArgumentsError extends UserError
{
    /** @var InvalidArgumentError */
    private $errors = [];

    public function __construct(array $errors, $message = '', $code = 0, \Exception $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return InvalidArgumentError[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Return a serializable array of validation errors for each argument.
     *
     * @return array
     */
    public function toState()
    {
        $state = [];
        foreach ($this->getErrors() as $error) {
            $state[$error->getName()] = [];
            foreach ($error->getErrors() as $violation) {
                $state[$error->getName()][] = [
                    'path' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                    'code' => $violation->getCode(),
                ];
            }
        }

        return $state;
    }
}
