<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class PluralResolver
{
    public function __invoke($username, ResolveInfo $info)
    {
        $lang = $info->rootValue['lang'];

        return [
            'username' => $username,
            'url' => \sprintf('www.facebook.com/%s?lang=%s', $username, $lang),
        ];
    }
}
