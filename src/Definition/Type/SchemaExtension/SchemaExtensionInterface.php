<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Type\SchemaExtension;

use GraphQL\Type\Schema;

interface SchemaExtensionInterface
{
    public function process(Schema $schema); // @phpstan-ignore-line
}
