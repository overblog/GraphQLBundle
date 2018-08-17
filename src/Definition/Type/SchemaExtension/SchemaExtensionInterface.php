<?php

namespace Overblog\GraphQLBundle\Definition\Type\SchemaExtension;

use GraphQL\Type\Schema;

interface SchemaExtensionInterface
{
    public function process(Schema $schema);
}
