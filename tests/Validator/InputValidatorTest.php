<?php declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Validator;

use Overblog\GraphQLBundle\Validator\InputValidator;
use Overblog\GraphQLBundle\Validator\ValidatorFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Validator\ConstraintValidatorFactory;

/**
 * Class InputValidatorTest
 *
 * @author Timur Murtukov <murtukov@gmail.com>
 */
class InputValidatorTest extends TestCase
{
    public function testNoDefaultValidatorException()
    {
        $factory = new ValidatorFactory(null, new ConstraintValidatorFactory(), null);

        $this->expectException(ServiceNotFoundException::class);

        new InputValidator([], null, $factory, []);
    }
}
