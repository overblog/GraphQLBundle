<?php

namespace Overblog\GraphBundle\Tests\Functional\app\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class PluralResolver
{
    public function resolveSingleInput($username, ResolveInfo $info)
    {
        $lang = $info->rootValue['lang'];

        return [
            'username' => $username,
            'url' => sprintf('www.facebook.com/%s?lang=%s', $username, $lang)
        ];
    }
}
