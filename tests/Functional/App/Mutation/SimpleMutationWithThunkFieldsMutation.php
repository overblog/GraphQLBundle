<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
