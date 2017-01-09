<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Tests\Functional\app\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class PluralResolver
{
    public function resolveSingleInput($username, ResolveInfo $info)
    {
        $lang = $info->rootValue['lang'];

        return [
            'username' => $username,
            'url' => sprintf('www.facebook.com/%s?lang=%s', $username, $lang),
        ];
    }
}
