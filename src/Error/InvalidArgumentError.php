<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Error;

use GraphQL\Error\UserError;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class InvalidArgumentError.
 */
class InvalidArgumentError extends UserError
{
    /**
     * @var string
     */
    private $name;

    /** @var ConstraintViolationListInterface */
    private $errors = [];

    public function __construct($name, ConstraintViolationListInterface $errors, $message = '', $code = 0, \Exception $previous = null)
    {
        $this->name = $name;
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
