<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\app\Mutation;

class SimpleMutationWithThunkFieldsMutation
{
    public function mutate($value)
    {
        return ['result' => $value['inputData']];
    }
}
