<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Type\SchemaExtension;

use GraphQL\Type\Schema;

final class WarningOnDeprecatedFieldsExtension implements SchemaExtensionInterface
{
    public function process(Schema $schema)
    {
        // Here we should check if fields of incoming GraphQL query are deprecated
    }
}
