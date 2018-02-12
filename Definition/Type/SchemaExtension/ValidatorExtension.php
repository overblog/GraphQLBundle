<?php

namespace Overblog\GraphQLBundle\Definition\Type\SchemaExtension;

use GraphQL\Type\Schema;

final class ValidatorExtension implements SchemaExtensionInterface
{
    public function process(Schema $schema)
    {
        $schema->assertValid();
    }
}
