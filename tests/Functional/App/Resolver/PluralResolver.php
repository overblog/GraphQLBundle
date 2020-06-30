<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\App\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use function sprintf;

class PluralResolver
{
    public function __invoke(string $username, ResolveInfo $info): array
    {
        $lang = $info->rootValue['lang'];

        return [
            'username' => $username,
            'url' => sprintf('www.facebook.com/%s?lang=%s', $username, $lang),
        ];
    }
}
