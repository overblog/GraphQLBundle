<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Exception;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\ClassesTypesMap;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\DoctrineTypeGuesser;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\TypeGuessingException;
use ReflectionClass;

class DoctrineTypeGuesserTest extends TestCase
{
    protected $property;

    public function testGuessError(): void
    {
        $refClass = new ReflectionClass(__CLASS__);
        $docBlockGuesser = new DoctrineTypeGuesser(new ClassesTypesMap());

        try {
            $docBlockGuesser->guessType($refClass, $refClass);
        } catch (Exception $e) {
            $this->assertInstanceOf(TypeGuessingException::class, $e);
            $this->assertStringContainsString('Doctrine type guesser only apply to properties.', $e->getMessage());
        }

        try {
            $docBlockGuesser->guessType($refClass, $refClass->getProperty('property'));
        } catch (Exception $e) {
            $this->assertInstanceOf(TypeGuessingException::class, $e);
            $this->assertStringContainsString('No Doctrine ORM annotation found.', $e->getMessage());
        }
    }
}
