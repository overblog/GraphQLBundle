<?php declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

use Overblog\GraphQLBundle\Definition\ArgumentInterface;
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
     * @param ArgumentInterface $args
     * @param InputValidator    $validator
     *
     * @return bool
     * @throws ArgumentsValidationException
     */
    public function createUser(ArgumentInterface $args, InputValidator $validator)
    {
        $validator->validate();

        return true;
    }

}
