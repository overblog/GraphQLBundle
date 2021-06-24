<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Config\Parser;

use Doctrine\ORM\Mapping\Column;
use Exception;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\ClassesTypesMap;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\DoctrineTypeGuesser;
use Overblog\GraphQLBundle\Config\Parser\MetadataParser\TypeGuesser\TypeGuessingException;
use ReflectionClass;

class DoctrineTypeGuesserTest extends TestCase
{
    // @phpstan-ignore-next-line
    protected $property;

    public static function isDoctrineInstalled(): bool
    {
        return class_exists(Column::class);
    }

    public function testGuessError(): void
    {
        if (!self::isDoctrineInstalled()) {
            $this->markTestSkipped('Doctrine ORM is not installed');
        }

        $refClass = new ReflectionClass(__CLASS__);
        $doctrineGuesser = new DoctrineTypeGuesser(new ClassesTypesMap());

        try {
            // @phpstan-ignore-next-line
            $doctrineGuesser->guessType($refClass, $refClass);
        } catch (Exception $e) {
            $this->assertInstanceOf(TypeGuessingException::class, $e);
            $this->assertStringContainsString('Doctrine type guesser only apply to properties.', $e->getMessage());
        }

        try {
            $doctrineGuesser->guessType($refClass, $refClass->getProperty('property'));
        } catch (Exception $e) {
            $this->assertInstanceOf(TypeGuessingException::class, $e);
            $this->assertStringContainsString('No Doctrine ORM annotation found.', $e->getMessage());
        }
    }

    public function testDoctrineOrmNotInstalled(): void
    {
        if (self::isDoctrineInstalled()) {
            $this->markTestSkipped('Doctrine ORM is installed');
        }

        $this->expectException(TypeGuessingException::class);
        $this->expectExceptionMessageMatches('/^You must install doctrine\/orm/');

        $refClass = new ReflectionClass(__CLASS__);
        $doctrineGuesser = new DoctrineTypeGuesser(new ClassesTypesMap());
        $doctrineGuesser->guessType($refClass, $refClass->getProperty('property'));
    }
}
