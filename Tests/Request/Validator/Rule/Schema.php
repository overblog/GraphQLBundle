<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Request\Validator\Rule;

use GraphQL\Schema as GraphQLSchema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Schema
{
    private static $schema;

    private static $dogType;

    private static $humanType;

    private static $queryRootType;

    /**
     * @return GraphQLSchema
     */
    public static function buildSchema()
    {
        if (null !== self::$schema) {
            return self::$schema;
        }

        static::buildHumanType();
        static::buildDogType();

        self::$schema = new GraphQLSchema(static::buildQueryRootType());

        return self::$schema;
    }

    public static function buildQueryRootType()
    {
        if (null !== self::$queryRootType) {
            return self::$queryRootType;
        }

        self::$queryRootType = new ObjectType([
            'name' => 'QueryRoot',
            'fields' => [
                'human' => [
                    'type' => self::buildHumanType(),
                ],
            ],
        ]);

        return self::$queryRootType;
    }

    public static function buildHumanType()
    {
        if (null !== self::$humanType) {
            return self::$humanType;
        }

        self::$humanType = new ObjectType(
            [
                'name' => 'Human',
                'fields' => [
                    'firstName' => ['type' => Type::nonNull(Type::string())],
                    'Dog' => [
                        'type' => function () {
                            return Type::nonNull(
                                Type::listOf(
                                    Type::nonNull(self::buildDogType())
                                )
                            );
                        },
                    ],
                ],
            ]
        );

        return self::$humanType;
    }

    public static function buildDogType()
    {
        if (null !== self::$dogType) {
            return self::$dogType;
        }

        self::$dogType = new ObjectType(
            [
                'name' => 'Dog',
                'fields' => [
                    'name' => ['type' => Type::nonNull(Type::string())],
                    'master' => [
                        'type' => self::buildHumanType(),
                    ],
                ],
            ]
        );

        return self::$dogType;
    }
}
