<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Validator;

use Overblog\GraphQLBundle\Validator\InputValidator;
use Overblog\GraphQLBundle\Validator\ValidatorFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use function class_exists;

class InputValidatorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (!class_exists('Symfony\\Component\\Validator\\Validation')) {
            $this->markTestSkipped('Symfony validator component is not installed');
        }
    }

    public function testNoDefaultValidatorException(): void
    {
        $factory = new ValidatorFactory(new ConstraintValidatorFactory(), null);

        $this->expectException(ServiceNotFoundException::class);

        new InputValidator([], null, $factory, []);
    }
}
