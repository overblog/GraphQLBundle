<?php declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\MutationInterface;
use Overblog\GraphQLBundle\Exception\ArgumentsValidationException;
use Overblog\GraphQLBundle\Validator\InputValidator;

/**
 * Class InputValidatorMutation
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class InputValidatorMutation implements MutationInterface
{
    /**
     * @param Argument       $args
     * @param InputValidator $validator
     *
     * @return bool
     * @throws ArgumentsValidationException
     */
    public function mutationMock(Argument $args, InputValidator $validator): bool
    {
        $validator->validate($args['groups']);

        return true;
    }

    public function noValidation(): bool
    {
        return true;
    }
}
