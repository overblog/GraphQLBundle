<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Type;

use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\ScalarTypeExtensionNode;
use GraphQL\Type\Definition\CustomScalarType as BaseCustomScalarType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;
use function call_user_func;
use function is_callable;
use function sprintf;
use function uniqid;

/**
 * @phpstan-type CustomScalarConfig array{
 *   name?: string|null,
 *   description?: string|null,
 *   serialize: callable(mixed): mixed,
 *   parseValue?: callable(mixed): mixed,
 *   parseLiteral?: callable(Node $valueNode, array|null $variables): mixed,
 *   astNode?: ScalarTypeDefinitionNode|null,
 *   extensionASTNodes?: array<ScalarTypeExtensionNode>|null,
 *   scalarType?: ScalarType|callable(): ScalarType|null,
 * }
 */
class CustomScalarType extends BaseCustomScalarType
{
    /** @phpstan-var CustomScalarConfig */
    public array $config;

    /**
     * @phpstan-param CustomScalarConfig $config
     */
    public function __construct(array $config)
    {
        $config['name'] ??= uniqid('CustomScalar', true);

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($value): mixed
    {
        return $this->call('serialize', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function parseValue($value): mixed
    {
        return $this->call('parseValue', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function parseLiteral(/* GraphQL\Language\AST\ValueNode */ $valueNode, array $variables = null): mixed
    {
        return $this->call('parseLiteral', $valueNode);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function call(string $type, $value)
    {
        if (!isset($this->config['scalarType'])) {
            return parent::$type($value);
        }

        $scalarType = match (true) {
            $this->config['scalarType'] instanceof ScalarType => $this->config['scalarType'],
            is_callable($this->config['scalarType']) => $this->config['scalarType'](),
            default => $this->config['scalarType'],
        };

        return call_user_func([$scalarType, $type], $value); // @phpstan-ignore-line
    }

    public function assertValid(): void
    {
        if (!isset($this->config['scalarType'])) {
            parent::assertValid();

            return;
        }

        $scalarType = match (true) {
            $this->config['scalarType'] instanceof ScalarType => $this->config['scalarType'],
            is_callable($this->config['scalarType']) => $this->config['scalarType'](),
            default => $this->config['scalarType'],
        };

        if (!$scalarType instanceof ScalarType) {
            throw new InvariantViolation(
                sprintf(
                    '%s must provide a valid "scalarType" instance of %s but got: %s',
                    $this->name,
                    ScalarType::class,
                    Utils::printSafe($scalarType)
                )
            );
        }
    }
}
