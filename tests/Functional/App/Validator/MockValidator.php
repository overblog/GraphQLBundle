<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Just a dummy validator
 */
class MockValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
    }
}
