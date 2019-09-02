<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Validator;

use Overblog\GraphQLBundle\Validator\InputValidator;
use Overblog\GraphQLBundle\Validator\ValidatorFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Validator\ConstraintValidatorFactory;

/**
 * Class InputValidatorTest.
 */
class InputValidatorTest extends TestCase
{
    public function testNoDefaultValidatorException(): void
    {
        $factory = new ValidatorFactory(null, new ConstraintValidatorFactory(), null);

        $this->expectException(ServiceNotFoundException::class);

        new InputValidator([], null, $factory, []);
    }
}
