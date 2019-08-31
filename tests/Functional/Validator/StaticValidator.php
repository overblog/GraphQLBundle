<?php declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use DateTime;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class StaticValidator
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class StaticValidator
{
    /**
     * @param                           $value
     * @param ExecutionContextInterface $context
     * @param                           $payload
     *
     * @throws \Exception
     */
    public static function greaterThan($value, ExecutionContextInterface $context, $payload)
    {
        $value = new DateTime($value);
        $limit = new DateTime($payload);

        if ($value > $limit) {
            $context->buildViolation("You should be at least 18 years old!")->addViolation();
        }
    }
}
