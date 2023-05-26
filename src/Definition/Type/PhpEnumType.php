<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Type;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use GraphQL\Language\AST\EnumTypeExtensionNode;
use GraphQL\Language\AST\EnumValueNode;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Utils\Utils;
use ReflectionEnum;
use UnitEnum;

/**
 * @phpstan-import-type EnumValues from EnumType
 *
 * @phpstan-type PhpEnumTypeConfig array{
 *   name?: string|null,
 *   description?: string|null,
 *   enumClass?: class-string<\UnitEnum>|null,
 *   values?: EnumValues|callable(): EnumValues,
 *   astNode?: EnumTypeDefinitionNode|null,
 *   extensionASTNodes?: array<int, EnumTypeExtensionNode>|null
 * }
 */
class PhpEnumType extends EnumType
{
    /** @var class-string<UnitEnum>|null */
    protected ?string $enumClass = null;

    /**
     * @phpstan-param PhpEnumTypeConfig $config
     */
    public function __construct(array $config)
    {
        if (isset($config['enumClass'])) {
            $this->enumClass = $config['enumClass'];
            if (!enum_exists($this->enumClass)) {
                throw new Error(sprintf('Enum class "%s" does not exist.', $this->enumClass));
            }
            unset($config['enumClass']);
        }

        if (!isset($config['values'])) {
            $config['values'] = [];
        }

        parent::__construct($config);
        if ($this->enumClass) {
            $configValues = $this->config['values'] ?? [];
            if (is_callable($configValues)) {
                $configValues = $configValues();
            }
            $reflection = new ReflectionEnum($this->enumClass);

            $enumDefinitions = [];
            foreach ($reflection->getCases() as $case) {
                $enumDefinitions[$case->getName()] = ['value' => $case->getName()];
            }

            foreach ($configValues as $name => $config) {
                if (!isset($enumDefinitions[$name])) {
                    throw new Error("Enum value {$name} is not defined in {$this->enumClass}");
                }
                $enumDefinitions[$name]['description'] = $config['description'] ?? null;
                $enumDefinitions[$name]['deprecationReason'] = $config['deprecationReason'] ?? null;
            }

            $this->config['values'] = $enumDefinitions;
        }
    }

    public function isEnumPhp(): bool
    {
        return null !== $this->enumClass;
    }

    public function parseValue($value): mixed
    {
        if ($this->enumClass) {
            try {
                return (new ReflectionEnum($this->enumClass))->getCase($value)->getValue();
            } catch (Exception $e) {
                throw new Error("Cannot represent enum of class {$this->enumClass} from value {$value}: ".$e->getMessage());
            }
        }

        return parent::parseValue($value);
    }

    public function parseLiteral(Node $valueNode, array $variables = null): mixed
    {
        if ($this->enumClass) {
            if (!$valueNode instanceof EnumValueNode) {
                throw new Error("Cannot represent enum of class {$this->enumClass} from node: {$valueNode->__toString()} is not an enum value");
            }
            try {
                return (new ReflectionEnum($this->enumClass))->getCase($valueNode->value)->getValue();
            } catch (Exception $e) {
                throw new Error("Cannot represent enum of class {$this->enumClass} from literal {$valueNode->value}: ".$e->getMessage());
            }
        }

        return parent::parseLiteral($valueNode, $variables);
    }

    public function serialize($value): mixed
    {
        if ($this->enumClass) {
            if (!$value instanceof $this->enumClass) {
                $valueStr = Utils::printSafe($value);
                throw new SerializationError("Cannot serialize value {$valueStr} as it must be an instance of enum {$this->enumClass}.");
            }

            return $value->name;
        }

        return parent::serialize($value);
    }
}
