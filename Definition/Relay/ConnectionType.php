<?php

namespace Overblog\GraphBundle\Definition\Relay;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Config;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Utils;

class ConnectionType extends ObjectType
{
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
            'resolveNode' => Config::CALLBACK
        ]);

        if (!static::$pageInfoType instanceof PageInfoType) {
            static::$pageInfoType = new PageInfoType();
        }

        /** @var ObjectType $nodeType */
        $nodeType = $config['nodeType'];
        $name = str_replace('Connection', '', $config['name']);
        if (empty($name)) {
            $name = $config['name'];
        }
        $edgeFields = empty($config['edgeFields'])? [] : $config['edgeFields'];
        $connectionFields = empty($config['connectionFields'])? [] : $config['connectionFields'];
        $resolveNode = empty($config['resolveNode'])? null : $config['resolveNode'];
        $resolveCursor = empty($config['resolveCursor'])? null : $config['resolveCursor'];

        $edgeType = new EdgeType([
            'name' => $name . 'Edge',
            'description' => 'An edge in a connection.',
            'fields' => array_merge(
                [
                    'node' => [
                        'type' => $nodeType,
                        'resolve' => $resolveNode,
                        'description' => 'The item at the end of the edge.'
                    ],
                    'cursor' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => $resolveCursor,
                        'description' => 'A cursor for use in pagination.'
                    ]
                ],
                $edgeFields
            )
        ]);

        parent::__construct([
            'name' => $name . 'Connection',
            'description' => 'A connection to a list of items.',
            'fields' => array_merge(
                [
                    'pageInfo' => [
                        'type' => Type::nonNull(static::$pageInfoType),
                        'description' => 'Information to aid in pagination.'
                    ],
                    'edges' => [
                        'type' => Type::listOf($edgeType),
                        'description' => 'Information to aid in pagination.'
                    ]
                ],
                $connectionFields
            )
        ]);
    }
}
