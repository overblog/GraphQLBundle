<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\Mutation;

class SimpleMutationWithThunkFieldsMutation
{
    private static $hasMutate = false;

    public static function hasMutate($reset = false)
    {
        $hasMutate = self::$hasMutate;

        if ($reset) {
            static::resetHasMutate();
        }

        return $hasMutate;
    }

    public static function resetHasMutate()
    {
        self::$hasMutate = false;
    }

    public function mutate($value)
    {
        self::$hasMutate = true;

        return ['result' => $value['inputData']];
    }
}
