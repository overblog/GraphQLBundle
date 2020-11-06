<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use DateTime;
use Exception;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class StaticValidator
{
    /**
     * @param mixed $value
     * @param mixed $payload
     *
     * @throws Exception
     */
    public static function greaterThan($value, ExecutionContextInterface $context, $payload): void
    {
        $value = new DateTime($value);
        $limit = new DateTime($payload);

        if ($value > $limit) {
            $context->buildViolation('You should be at least 18 years old!')->addViolation();
        }
    }

    /**
     * @param mixed $object
     * @param mixed $payload
     */
    public static function validateClass($object, ExecutionContextInterface $context, $payload): void
    {
        if ('Lorem Ipsum' === $object->string1) {
            $context->buildViolation('Class is invalid');
        }
    }
}
