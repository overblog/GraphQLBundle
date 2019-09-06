<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class ConstraintNotFoundTest extends TestCase
{
    public function testExceptionThrown()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Constraint class 'Symfony\Component\Validator\Constraints\BlahBlah' doesn't exist.");

        parent::setUp();
        static::bootKernel(['test_case' => 'invalidValidationConfig']);
    }
}
