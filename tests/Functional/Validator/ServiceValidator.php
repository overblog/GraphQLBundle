<?php declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Validator;

class ServiceValidator
{
    public function isZipCodeValid($code)
    {
        if ($code > 9999 && $code < 999999) {
            return true;
        }

        return false;
    }
}
