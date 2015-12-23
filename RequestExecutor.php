<?php

namespace Overblog\GraphBundle;

use GraphQL\Executor\Executor;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Schema;

class RequestExecutor
{
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function execute(array $data, $context = null)
    {
        $source = new Source($data['query']);
        $ast = Parser::parse($source);

        return Executor::execute(
            $this->schema,
            $ast,
            $context,
            $data['variables'],
            $data['operationName']
        );
    }
}
