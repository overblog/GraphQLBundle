<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Relay\Mutation;

use Overblog\GraphQLBundle\Relay\Mutation\MutationFieldDefinition;
use PHPUnit\Framework\TestCase;

class MutationFieldDefinitionTest extends TestCase
{
    /** @var MutationFieldDefinition */
    private $definition;

    public function setUp(): void
    {
        $this->definition = new MutationFieldDefinition();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Mutation "mutateAndGetPayload" config is required.
     */
    public function testUndefinedMutateAndGetPayloadConfig(): void
    {
        $this->definition->toMappingDefinition([]);
    }
}
