<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;

class ServiceValidator
{
    public function isZipCodeValid(int $code): bool
    {
        if ($code > 9999 && $code < 999999) {
            return true;
        }

        return false;
    }

    public function resolveVariablesAccessible(?ArgumentInterface $args, ?ResolveInfo $info): bool
    {
        if ($args instanceof ArgumentInterface && $info instanceof ResolveInfo) {
            return true;
        }

        return false;
    }
}
