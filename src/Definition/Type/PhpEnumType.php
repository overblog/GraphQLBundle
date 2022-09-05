<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Definition\Type;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Error\SerializationError;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Utils\Utils;
use ReflectionEnum;

class PhpEnumType extends EnumType
{
    public function parseValue($value): mixed
    {
        if ($this->isPhpEnum()) {
            try {
                return (new ReflectionEnum($this->config['enumClass']))->getCase($value)->getValue();
            } catch (Exception $e) {
                throw new Error("Cannot represent enum of class {$this->config['enumClass']} from value {$value}: ".$e->getMessage());
            }
        }

        return parent::parseValue($value);
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): mixed
    {
        if ($this->isPhpEnum()) {
            try {
                return (new ReflectionEnum($this->config['enumClass']))->getCase($valueNode->value)->getValue();
            } catch (Exception $e) {
                throw new Error("Cannot represent enum of class {$this->config['enumClass']} from value {$valueNode->value}: ".$e->getMessage());
            }
        }

        return parent::parseLiteral($valueNode, $variables);
    }

    public function serialize($value): mixed
    {
        if ($this->isPhpEnum()) {
            if (!$value instanceof $this->config['enumClass']) {
                $valueStr = Utils::printSafe($value);
                throw new SerializationError("Cannot serialize value {$valueStr} as it must be an instance of enum {$this->config['enumClass']}.");
            }

            return $value->name;
        }

        return parent::serialize($value);
    }

    protected function isPhpEnum(): bool
    {
        return isset($this->config['enumClass']);
    }
}
