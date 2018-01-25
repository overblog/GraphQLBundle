<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Utils;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Resolver\ResolverMap;

class SchemaLanguageQueryResolverMap extends ResolverMap
{
    protected function map()
    {
        return [
            'Query' => [
                self::RESOLVE_FIELD => function ($value, Argument $args, \ArrayObject $context, ResolveInfo $info) {
                    if ('character' === $info->fieldName) {
                        $characters = Characters::getCharacters();
                        $id = (int) $args['id'];
                        if (isset($characters[$id])) {
                            return $characters[$id];
                        }
                    }

                    return null;
                },
                'findHumansByDateOfBirth' => function ($value, Argument $args) {
                    $years = $args['years'];

                    return array_filter(Characters::getHumans(), function ($human) use ($years) {
                        return in_array($human['dateOfBirth'], $years);
                    });
                },
                'humans' => [Characters::class, 'getHumans'],
                'direwolves' => [Characters::class, 'getDirewolves'],
            ],
            'Character' => [
                self::RESOLVE_TYPE => function ($value) {
                    return Characters::TYPE_HUMAN === $value['type'] ? 'Human' : 'Direwolf';
                },
            ],
            'Human' => [
                'direwolf' => function ($value) {
                    $direwolves = Characters::getDirewolves();
                    if (isset($direwolves[$value['direwolf']])) {
                        return $direwolves[$value['direwolf']];
                    } else {
                        return null;
                    }
                },
            ],
            // enum internal values
            'Status' => [
                'ALIVE' => 1,
                'DECEASED' => 0,
            ],
            // custom scalar
            'Year' => [
                self::SERIALIZE => function ($value) {
                    return sprintf('%s AC', $value);
                },
                self::PARSE_VALUE => function ($value) {
                    if (!is_string($value)) {
                        throw new Error(sprintf('Cannot represent following value as a valid year: %s.', Utils::printSafeJson($value)));
                    }

                    return (int) str_replace(' AC', '', $value);
                },
                self::PARSE_LITERAL => function ($valueNode) {
                    if (!$valueNode instanceof StringValueNode) {
                        throw new Error('Query error: Can only parse strings got: '.$valueNode->kind, [$valueNode]);
                    }

                    return (int) str_replace(' AC', '', $valueNode->value);
                },
            ],
        ];
    }
}
