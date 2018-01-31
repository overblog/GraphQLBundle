<?php

namespace Overblog\GraphQLBundle\Definition\Type;

use GraphQL\Type\Definition\CustomScalarType as BaseCustomScalarType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class CustomScalarType extends BaseCustomScalarType
{
    public function __construct(array $config = [])
    {
        $config['name'] = isset($config['name']) ? $config['name'] : uniqid('CustomScalar');
        parent::__construct($config);

        $this->config['scalarType'] = isset($this->config['scalarType']) ? $this->config['scalarType'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($value)
    {
        return $this->call('serialize', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function parseValue($value)
    {
        return $this->call('parseValue', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function parseLiteral(/* GraphQL\Language\AST\ValueNode */ $valueNode)
    {
        return $this->call('parseLiteral', $valueNode);
    }

    private function call($type, $value)
    {
        if (isset($this->config['scalarType'])) {
            $scalarType = $this->config['scalarType'];
            $scalarType = is_callable($scalarType) ? $scalarType() : $scalarType;

            return call_user_func([$scalarType, $type], $value);
        } else {
            return parent::$type($value);
        }
    }

    public function assertValid()
    {
        if (isset($this->config['scalarType'])) {
            $scalarType = $this->config['scalarType'];
            if (is_callable($scalarType)) {
                $scalarType = $scalarType();
            }

            Utils::invariant(
                $scalarType instanceof ScalarType,
                sprintf(
                    '%s must provide a valid "scalarType" instance of %s but got: %s',
                    $this->name,
                    ScalarType::class,
                    Utils::printSafe($scalarType)
                )
            );
        } else {
            parent::assertValid();
        }
    }
}
