<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class DisableIntrospectionTest extends TestCase
{
    private string $introspectionQuery = <<<'EOF'
    query {
      __schema {
        types {
          name
          description
        }
      }
    }
    EOF;

    public function testIntrospectionDisabled(): void
    {
        $expected = [
            'errors' => [
                [
                    'message' => 'GraphQL introspection is not allowed, but the query contained __schema or __type',
                    'extensions' => ['category' => 'graphql'],
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
