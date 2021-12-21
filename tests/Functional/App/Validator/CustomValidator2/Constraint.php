<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Validator\CustomValidator2;

use Overblog\GraphQLBundle\Tests\Functional\App\Validator\MockValidator;
use Symfony\Component\Validator\Constraint as BaseConstraint;

/**
 * @Annotation
 */
class Constraint extends BaseConstraint
{
    public string $message = 'Mock constraint';

    /**
     * @return class-string
     */
    public function validatedBy(): string
    {
        return MockValidator::class;
    }
}
