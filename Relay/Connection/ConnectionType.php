<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Relay\Connection;

use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils;
use Overblog\GraphQLBundle\Definition\MergeFieldTrait;

class ConnectionType extends ObjectType
{
    use MergeFieldTrait;

    /** @var  PageInfoType */
    private static $pageInfoType;

    public function __construct(array $config)
    {
        Utils::invariant(!empty($config['name']), 'Every type is expected to have name');

        Config::validate($config, [
            'name' => Config::STRING | Config::REQUIRED,
            'nodeType' => Config::OBJECT_TYPE | Config::CALLBACK | Config::REQUIRED,
            'edgeFields' => Config::arrayOf(
                FieldDefinition::getDefinition(),
                Config::KEY_AS_NAME
            ),
            'connectionFields' => Config::arrayOf(
                FieldDefinition::getDefinition(),
                Config::KEY_AS_NAME
            ),
            'resolveCursor' => Config::CALLBACK,
            'resolveNode' => Config::CALLBACK,
        ]);

        if (!self::$pageInfoType instanceof PageInfoType) {
            self::$pageInfoType = new PageInfoType();
        }

        /** @var ObjectType $nodeType */
        $nodeType = $config['nodeType'];
        $name = str_replace('Connection', '', $config['name']);
        if (empty($name)) {
            $name = $config['name'];
        }
        $edgeFields = empty($config['edgeFields']) ? [] : $config['edgeFields'];
        $connectionFields = empty($config['connectionFields']) ? [] : $config['connectionFields'];
        $resolveNode = empty($config['resolveNode']) ? null : $config['resolveNode'];
        $resolveCursor = empty($config['resolveCursor']) ? null : $config['resolveCursor'];

        $edgeType = new EdgeType([
            'name' => $name.'Edge',
            'description' => 'An edge in a connection.',
            'fields' => $this->getFieldsWithDefaults(
                $edgeFields,
                [
                    'node' => [
                        'type' => $nodeType,
                        'resolve' => $resolveNode,
                        'description' => 'The item at the end of the edge.',
                    ],
                    'cursor' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => $resolveCursor,
                        'description' => 'A cursor for use in pagination.',
                    ],
                ]
            ),
        ]);

        parent::__construct([
            'name' => $name.'Connection',
            'description' => 'A connection to a list of items.',
            'fields' => $this->getFieldsWithDefaults(
                $connectionFields,
                [
                    'pageInfo' => [
                        'type' => Type::nonNull(self::$pageInfoType),
                        'description' => 'Information to aid in pagination.',
                    ],
                    'edges' => [
                        'type' => Type::listOf($edgeType),
                        'description' => 'Information to aid in pagination.',
                    ],
                ]
            ),
        ]);
    }
}
