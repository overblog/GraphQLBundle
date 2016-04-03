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
use Overblog\GraphQLBundle\Definition\ObjectType;
use Overblog\GraphQLBundle\Definition\Type;

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
                    'args' => ['name' => ['type' => Type::string()]],
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
                    'dogs' => [
                        'type' => function () {
                            return Type::nonNull(
                                Type::listOf(
                                    Type::nonNull(self::buildDogType())
                                )
                            );
                        },
                        'complexity' => function ($childrenComplexity, $args) {
                            $complexity = isset($args['name']) ? 1 : 10;

                            return $childrenComplexity + $complexity;
                        },
                        'args' => ['name' => ['type' => Type::string()]],
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
