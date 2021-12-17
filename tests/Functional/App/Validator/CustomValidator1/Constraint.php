<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Validator\CustomValidator1;

use Overblog\GraphQLBundle\Tests\Functional\App\Validator\MockValidator;
use Symfony\Component\Validator\Constraint as BaseConstraint;

/**
 * This and CustomValidator2/Constraint should be named same,
 * to test that generated type class doesn't include them both into use statement,
 * which produced a namespace conflict
 *
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
