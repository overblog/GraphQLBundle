<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Relay\Node;

use Overblog\GraphQLBundle\Relay\Mutation\MutationFieldDefinition;
use PHPUnit\Framework\TestCase;

class MutationFieldDefinitionTest extends TestCase
{
    /**
     * @var MutationFieldDefinition
     */
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
