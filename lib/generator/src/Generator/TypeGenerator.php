<?php declare(strict_types=1);

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
    protected function generateOutputFields(array $config): string
    {
        return  \sprintf(static::CLOSURE_TEMPLATE, '', $this->processFromArray($config['fields'], 'OutputField'));
    }

    protected function generateInputFields(array $config): string
    {
        return \sprintf(static::CLOSURE_TEMPLATE, '', $this->processFromArray($config['fields'], 'InputField'));
    }

    protected function generateArgs(array $fields): string
    {
        return isset($fields['args']) ? $this->processFromArray($fields['args'], 'Arg') : '[]';
    }

    protected function generateValues(array $config): string
    {
        return $this->processFromArray($config['values'], 'Value');
    }

    protected function generateValue(array $value): string
    {
        return $this->varExportFromArrayValue($value, 'value');
    }

    protected function generateDescription(array $value): string
    {
        return $this->varExportFromArrayValue($value, 'description');
    }

    protected function generateName(array $value): string
    {
        return $this->varExportFromArrayValue($value, 'name');
    }

    protected function generateDeprecationReason(array $value): string
    {
        return $this->varExportFromArrayValue($value, 'deprecationReason');
    }

    protected function generateDefaultValue(array $value): string
    {
        $key = 'defaultValue';
        if (!\array_key_exists($key, $value)) {
            return '';
        }

        return \sprintf("\n<spaces>'%s' => %s,", $key, $this->varExportFromArrayValue($value, $key));
    }

    protected function generateType(array $value): string
    {
        $type = 'null';

        if (isset($value['type'])) {
            $type = $this->typeAlias2String($value['type']);
        }

        return $type;
    }

    protected function generateInterfaces(array $value): string
    {
        return $this->resolveTypesCode($value, 'interfaces');
    }

    protected function generateTypes(array $value): string
    {
        return $this->resolveTypesCode($value, 'types');
    }

    protected function generateResolve(array $value): string
    {
        return $this->callableCallbackFromArrayValue($value, 'resolve', '$value, $args, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    protected function generateResolveType(array $value): string
    {
        return $this->callableCallbackFromArrayValue($value, 'resolveType', '$value, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    protected function generateIsTypeOf(array $value): string
    {
        return $this->callableCallbackFromArrayValue($value, 'isTypeOf', '$value, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    protected function generateResolveField(array $value): string
    {
        return $this->callableCallbackFromArrayValue($value, 'resolveField', '$value, $args, $context, \\GraphQL\\Type\\Definition\\ResolveInfo $info');
    }

    protected function generateComplexity(array $value): string
    {
        return $this->callableCallbackFromArrayValue($value, 'complexity', '$childrenComplexity, $args = []');
    }

    protected function generateSerialize(array $value): string
    {
        return $this->callableCallbackFromArrayValue($value, 'serialize', '$value');
    }

    protected function generateParseValue(array $value): string
    {
        return $this->callableCallbackFromArrayValue($value, 'parseValue', '$value');
    }

    protected function generateParseLiteral(array $value): string
    {
        return $this->callableCallbackFromArrayValue($value, 'parseLiteral', '$value');
    }
}
