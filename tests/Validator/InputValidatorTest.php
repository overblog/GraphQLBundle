<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Validator;

use ArrayObject;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\ResolverArgs;
use Overblog\GraphQLBundle\Validator\InputValidatorFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Validator\Validation;
use function class_exists;

class InputValidatorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (!class_exists(Validation::class)) {
            $this->markTestSkipped('Symfony validator component is not installed');
        }
    }

    public function testNoDefaultValidatorException(): void
    {
        $this->expectException(ServiceNotFoundException::class);

        $factory = new InputValidatorFactory(null, null, null);

        $factory->create(new ResolverArgs(
            true,
            new Argument(),
            new ArrayObject(),
            $this->getMockBuilder(ResolveInfo::class)->disableOriginalConstructor()->getMock(),
        ));
    }
}
