<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

use Overblog\GraphQLBundle\Definition\ArgumentInterface;

class SimpleMutationWithThunkFieldsMutation
{
    private static bool $hasMutate = false;

    public static function hasMutate(bool $reset = false): bool
    {
        $hasMutate = self::$hasMutate;

        if ($reset) {
            static::resetHasMutate();
        }

        return $hasMutate;
    }

    public static function resetHasMutate(): void
    {
        self::$hasMutate = false;
    }

    public function mutate(ArgumentInterface $value): array
    {
        self::$hasMutate = true;

        return ['result' => $value['inputData']];
    }
}
