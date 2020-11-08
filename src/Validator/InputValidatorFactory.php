<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InputValidatorFactory
{
    private ?ValidatorInterface $defaultValidator;
    private ?ConstraintValidatorFactoryInterface $constraintValidatorFactory;
    private ?TranslatorInterface $defaultTranslator;

    /**
     * InputValidatorFactory constructor.
     */
    public function __construct(
        ?ConstraintValidatorFactoryInterface $constraintValidatorFactory,
        ?ValidatorInterface $validator,
        ?TranslatorInterface $translator
    ) {
        $this->defaultValidator = $validator;
        $this->defaultTranslator = $translator;
        $this->constraintValidatorFactory = $constraintValidatorFactory;
    }

    public function create(array $resolverArgs): InputValidator
    {
        if (null === $this->defaultValidator) {
            throw new ServiceNotFoundException("The 'validator' service is not found. To use the 'InputValidator' you need to install the Symfony Validator Component first. See: 'https://symfony.com/doc/current/validation.html'");
        }

        return new InputValidator(
            $resolverArgs,
            $this->defaultValidator,
            $this->constraintValidatorFactory,
            $this->defaultTranslator
        );
    }
}
