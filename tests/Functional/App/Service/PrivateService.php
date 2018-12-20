<?php

namespace Overblog\GraphQLBundle\Tests\Functional\App\Service;

class PrivateService
{
    public function hasAccess()
    {
        return true;
    }
}
