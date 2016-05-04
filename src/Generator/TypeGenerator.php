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
        return $this->processFromArray($config['fields'], 'OutputField');
    }

    protected function generateInputFields(array $config)
    {
        return $this->processFromArray($config['fields'], 'InputField');
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
        return $this->varExportFromArrayValue($value, 'defaultValue');
    }

    protected function generateType(array $value)
    {
        if (isset($value['type'])) {
            $type = sprintf('function () <closureUseStatements>{ return %s; }', $this->typeAlias2String($value['type']));
        } else {
            $type = 'null';
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
        return $this->callableCallbackFromArrayValue($value, 'resolve', '$value, $args, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateResolveType(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'resolveType', '$value');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateIsTypeOf(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'isTypeOf', '$value, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateResolveField(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'resolveField', '$value, $args, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    /**
     * @param array $value
     * @return string
     */
    protected function generateComplexity(array $value)
    {
        return $this->callableCallbackFromArrayValue($value, 'complexity', '$childrenComplexity, $args = []');
    }
}
