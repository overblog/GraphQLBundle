<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Validator;

use Symfony\Component\Validator\AtLeastOneOf as BaseAtLeastOneOf;
use Symfony\Component\Validator\Constraint;

if (class_exists(BaseAtLeastOneOf::class)) {
    class AtLeastOneOf extends BaseAtLeastOneOf
    {
    }
} else {
    class AtLeastOneOf extends Constraint
    {
        public string $message = 'Mock constraint';

        /**
         * @var array
         */
        public $constraints = [];

        /**
         * @var bool
         */
        public $includeInternalMessages = true;

        /**
         * @return class-string
         */
        public function validatedBy(): string
        {
            return MockValidator::class;
        }
    }
}
