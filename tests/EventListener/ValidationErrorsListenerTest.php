<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\EventListener;

use GraphQL\Error\Error;
use Overblog\GraphQLBundle\Error\InvalidArgumentError;
use Overblog\GraphQLBundle\Error\InvalidArgumentsError;
use Overblog\GraphQLBundle\Event\ErrorFormattingEvent;
use Overblog\GraphQLBundle\EventListener\ValidationErrorsListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use function class_exists;

class ValidationErrorsListenerTest extends TestCase
{
    /** @var ValidationErrorsListener */
    private $listener;

    protected function setUp(): void
    {
        if (!class_exists('Symfony\\Component\\Validator\\Validation')) {
            $this->markTestSkipped('Symfony validator component is not installed');
        }
        $this->listener = new ValidationErrorsListener();
    }

    public function testOnErrorFormatting(): void
    {
        $invalidArguments = new InvalidArgumentsError([new InvalidArgumentError('invalid', new ConstraintViolationList([new ConstraintViolation('message', 'error_template', [], '', 'prop1', 'invalid')]))]);
        $formattedError = [];
        $event = new ErrorFormattingEvent(Error::createLocatedError($invalidArguments), $formattedError);
        $this->listener->onErrorFormatting($event);

        $this->assertEquals($event->getFormattedError()->getArrayCopy(), ['state' => ['invalid' => [0 => ['path' => 'prop1', 'message' => 'message', 'code' => null]]]]);
    }
}
