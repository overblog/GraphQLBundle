<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use ArrayObject;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use Overblog\GraphQLBundle\Tests\Functional\App\Type\YearScalarType;
use function array_filter;
use function in_array;

class SchemaLanguageQueryResolverMap extends ResolverMap
{
    protected function map(): array
    {
        return [
            'Query' => [
                self::RESOLVE_FIELD => $this,
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
                self::SCALAR_TYPE => function () {
                    return new YearScalarType();
                },
            ],
        ];
    }

    /**
     * @param null $_
     */
    public function __invoke($_, Argument $args, ArrayObject $context, ResolveInfo $info): ?array
    {
        if ('character' === $info->fieldName) {
            $characters = Characters::getCharacters();
            $id = (int) $args['id'];
            if (isset($characters[$id])) {
                return $characters[$id];
            }
        }

        return null;
    }
}
