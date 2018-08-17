<?php

namespace Overblog\GraphQLBundle\Tests\Relay\Node;

use Overblog\GraphQLBundle\Relay\Mutation\MutationFieldDefinition;
use PHPUnit\Framework\TestCase;

class MutationFieldDefinitionTest extends TestCase
{
    /** @var MutationFieldDefinition */
    private $definition;

    public function setUp()
    {
        $this->definition = new MutationFieldDefinition();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Mutation "mutateAndGetPayload" config is required.
     */
    public function testUndefinedMutateAndGetPayloadConfig()
    {
        $this->definition->toMappingDefinition([]);
    }
}
