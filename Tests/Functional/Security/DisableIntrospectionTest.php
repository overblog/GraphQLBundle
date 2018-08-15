<?php

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class DisableIntrospectionTest extends TestCase
{
    private $introspectionQuery = <<<'EOF'
query {
  __schema {
    types {
      name
      description
    }
  }
}
EOF;

    public function testIntrospectionDisabled()
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'GraphQL introspection is not allowed, but the query contained __schema or __type',
                    'category' => 'graphql',
                    'locations' => [
                        [
                            'line' => 2,
                            'column' => 3,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertResponse($this->introspectionQuery, $expected, self::ANONYMOUS_USER, 'disableIntrospection');
    }
}
