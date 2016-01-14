<?php

namespace Overblog\GraphBundle\Request;

use GraphQL\Executor\Executor as GraphQLExecutor;
use GraphQL\Language\Parser as  GraphQLParser;
use GraphQL\Language\Source;
use GraphQL\Schema;

class Executor
{
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function execute(array $data, $context = null)
    {
        $source = new Source($data['query']);
        $ast = GraphQLParser::parse($source);

        return GraphQLExecutor::execute(
            $this->schema,
            $ast,
            $context,
            $data['variables'],
            $data['operationName']
        );
    }
}
