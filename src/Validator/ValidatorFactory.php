<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator;

use Overblog\GraphQLBundle\Validator\Mapping\MetadataFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidatorFactory
{
    private ?TranslatorInterface $defaultTranslator;
    private ConstraintValidatorFactoryInterface $constraintValidatorFactory;

    public function __construct(ConstraintValidatorFactoryInterface $constraintValidatorFactory, ?TranslatorInterface $translator)
    {
        $this->defaultTranslator = $translator;
        $this->constraintValidatorFactory = $constraintValidatorFactory;
    }

    public function createValidator(MetadataFactory $metadataFactory): ValidatorInterface
    {
        $builder = Validation::createValidatorBuilder()
            ->setMetadataFactory($metadataFactory)
            ->setConstraintValidatorFactory($this->constraintValidatorFactory);

        if (null !== $this->defaultTranslator) {
            // @phpstan-ignore-next-line (only for Symfony 4.4)
            $builder
                ->setTranslator($this->defaultTranslator)
                ->setTranslationDomain('validators');
        }

        return $builder->getValidator();
    }
}
