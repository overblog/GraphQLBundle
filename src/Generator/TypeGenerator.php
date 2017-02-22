<?php

/*
 * This file is part of the OverblogGraphQLPhpGenerator package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLGenerator\Generator;

class TypeGenerator extends AbstractTypeGenerator
{
    protected function generateOutputFields(array $config)
    {
        return  sprintf(static::$closureTemplate, '', $this->processFromArray($config['fields'], 'OutputField'));
    }

    protected function generateInputFields(array $config)
    {
        return sprintf(static::$closureTemplate, '', $this->processFromArray($config['fields'], 'InputField'));
    }

    protected function generateArgs(array $fields)
    {
        return isset($fields['args']) ? $this->processFromArray($fields['args'], 'Arg') : '[]';
    }

    protected function generateValues(array $config)
    {
        return $this->processFromArray($config['values'], 'Value');
    }

    protected function generateValue(array $value)
    {
        return $this->varExportFromArrayValue($value, 'value');
    }

    protected function generateDescription(array $value)
    {
        return $this->varExportFromArrayValue($value, 'description');
    }

    protected function generateName(array $value)
    {
        return $this->varExportFromArrayValue($value, 'name');
    }

    protected function generateDeprecationReason(array $value)
    {
        return $this->varExportFromArrayValue($value, 'deprecationReason');
    }

    protected function generateDefaultValue(array $value)
    {
        $key = 'defaultValue';
        if (!array_key_exists($key, $value)) {
            return '';
        }

        return sprintf("\n<spaces>'%s' => %s,", $key, $this->varExportFromArrayValue($value, $key));
    }

    protected function generateType(array $value)
    {
        $type = 'null';

        if (isset($value['type'])) {
            $type = $this->typeAlias2String($value['type']);
        }

        return $type;
    }

    protected function generateInterfaces(array $value)
    {
        return $this->resolveTypesCode($value, 'interfaces');
    }

    protected function generateTypes(array $value)
    {
        return $this->resolveTypesCode($value, 'types');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateResolve(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'resolve', '$value, $args, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateResolveType(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'resolveType', '$value, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateIsTypeOf(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'isTypeOf', '$value, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateResolveField(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'resolveField', '$value, $args, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateComplexity(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'complexity', '$childrenComplexity, $args = []');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateSerialize(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'serialize', '$value');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateParseValue(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'parseValue', '$value');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateParseLiteral(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'parseLiteral', '$value');
    }
}
