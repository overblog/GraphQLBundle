<?php

namespace Overblog\GraphQLBundle\Error;

/**
 * Class UserErrors.
 */
class UserErrors extends UserFacingError
{
    /** @var UserError[] */
    private $errors = [];

    public function __construct(array $errors, $message = '', $code = 0, \Exception $previous = null)
    {
        $this->setErrors($errors);
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param UserError[]|string[] $errors
     */
    public function setErrors(array $errors)
    {
        foreach ($errors as $error) {
            $this->addError($error);
        }
    }

    /**
     * @param string|UserError $error
     *
     * @return $this
     */
    public function addError($error)
    {
        if (is_string($error)) {
            $error = new UserError($error);
        } elseif (!is_object($error) || !$error instanceof UserError) {
            throw new \InvalidArgumentException(sprintf('Error must be string or instance of %s.', UserError::class));
        }

        $this->errors[] = $error;

        return $this;
    }

    /**
     * @return UserError[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
