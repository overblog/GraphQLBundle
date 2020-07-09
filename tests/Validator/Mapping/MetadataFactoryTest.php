<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Validator\Mapping;

use GraphQL\Type\Definition\ObjectType;
use Overblog\GraphQLBundle\Validator\Mapping\MetadataFactory;
use Overblog\GraphQLBundle\Validator\Mapping\ObjectMetadata;
use Overblog\GraphQLBundle\Validator\ValidationNode;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Validator\Exception\NoSuchMetadataException;
use function class_exists;

class MetadataFactoryTest extends TestCase
{
    public function setUp(): void
    {
        if (!class_exists('Symfony\\Component\\Validator\\Validation')) {
            $this->markTestSkipped('Symfony validator component is not installed');
        }
        parent::setUp();
    }

    public function testMetadataFactoryHasObject(): void
    {
        $metadataFactory = new MetadataFactory();

        $type = new ObjectType(['name' => 'testType']);
        $validationNode = new ValidationNode($type);
        $objectMetadata = new ObjectMetadata($validationNode);

        $metadataFactory->addMetadata($objectMetadata);

        $hasMetadata = $metadataFactory->hasMetadataFor($validationNode);
        $metadata = $metadataFactory->getMetadataFor($validationNode);

        $this->assertTrue($hasMetadata);
        $this->assertSame($objectMetadata, $metadata);
    }

    public function testMetadataFactoryHasNoObject(): void
    {
        $metadataFactory = new MetadataFactory();

        $object = new stdClass();

        $hasMetadata = $metadataFactory->hasMetadataFor($object);
        $this->assertFalse($hasMetadata);

        $this->expectException(NoSuchMetadataException::class);
        $metadataFactory->getMetadataFor($object);
    }
}
