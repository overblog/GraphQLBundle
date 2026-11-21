<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Security;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

use function putenv;

/**
 * When "enable_introspection" is backed by an environment variable, the value
 * must be resolved at runtime. Before the fix it was evaluated at
 * container-compile time as a placeholder string (always truthy), so
 * introspection stayed enabled regardless of the env variable.
 *
 * @see https://github.com/overblog/GraphQLBundle/issues/1211
 */
final class EnableIntrospectionEnvVarTest extends TestCase
{
    private const CONFIG_NAME = 'enableIntrospectionEnvVar';
    private const ENV_DISABLED = 'false';
    private const ENV_ENABLED = 'true';
    private const ENV_VAR = 'GRAPHQL_ENABLE_INTROSPECTION';

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

    public function testIntrospectionDisabledViaEnvVar(): void
    {
        $previous = getenv(self::ENV_VAR);
        $this->setEnv(self::ENV_DISABLED);

        try {
            $expected = [
                'errors' => [
                    [
                        'message' => 'GraphQL introspection is not allowed, but the query contained __schema or __type',
                        'locations' => [
                            [
                                'line' => 2,
                                'column' => 3,
                            ],
                        ],
                    ],
                ],
            ];

            $this->assertResponse($this->introspectionQuery, $expected, self::ANONYMOUS_USER, self::CONFIG_NAME);
        } finally {
            $this->restoreEnv($previous);
        }
    }

    public function testIntrospectionEnabledViaEnvVar(): void
    {
        $previous = getenv(self::ENV_VAR);
        $this->setEnv(self::ENV_ENABLED);

        try {
            $client = self::createClientAuthenticated(self::ANONYMOUS_USER, self::CONFIG_NAME);
            $result = self::sendRequest($client, $this->introspectionQuery, true);

            static::assertArrayHasKey('data', $result);
            static::assertArrayNotHasKey('errors', $result);
        } finally {
            $this->restoreEnv($previous);
        }
    }

    /**
     * @param string|false $previous the value returned by getenv() before the test
     */
    private function restoreEnv($previous): void
    {
        if (false === $previous) {
            static::assertTrue(putenv(self::ENV_VAR));
            unset($_ENV[self::ENV_VAR], $_SERVER[self::ENV_VAR]);
        } else {
            static::assertTrue(putenv(self::ENV_VAR.'='.$previous));
            $_ENV[self::ENV_VAR] = $previous;
            $_SERVER[self::ENV_VAR] = $previous;
        }
    }

    private function setEnv(string $value): void
    {
        static::assertTrue(putenv(self::ENV_VAR.'='.$value));
        $_ENV[self::ENV_VAR] = $value;
        $_SERVER[self::ENV_VAR] = $value;
    }
}
