<?php

namespace Overblog\GraphQLBundle\Definition\Type;

use GraphQL\Type\Definition\CustomScalarType as BaseCustomScalarType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

class CustomScalarType extends BaseCustomScalarType
{
    public function __construct(array $config = [])
    {
        $config['name'] = isset($config['name']) ? $config['name'] : \uniqid('CustomScalar');
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
    public function parseLiteral(/* GraphQL\Language\AST\ValueNode */ $valueNode, array $variables = null)
    {
        return $this->call('parseLiteral', $valueNode);
    }

    private function call($type, $value)
    {
        if (isset($this->config['scalarType'])) {
            return \call_user_func([$this->loadScalarType(), $type], $value);
        } else {
            return parent::$type($value);
        }
    }

    public function assertValid()
    {
        if (isset($this->config['scalarType'])) {
            $scalarType = $this->loadScalarType();

            Utils::invariant(
                $scalarType instanceof ScalarType,
                \sprintf(
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

    private function loadScalarType()
    {
        if ($this->config['scalarType'] instanceof ScalarType) {
            return $this->config['scalarType'];
        } elseif (\is_callable($this->config['scalarType'])) {
            return $this->config['scalarType'] = $this->config['scalarType']();
        } else {
            return $this->config['scalarType'];
        }
    }
}
