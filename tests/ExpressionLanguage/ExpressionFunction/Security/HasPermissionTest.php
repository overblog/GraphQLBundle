<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasPermission;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class HasPermissionTest extends TestCase
{
    protected function getFunctions()
    {
        return [new HasPermission()];
    }

    public function testHasPermission(): void
    {
        $object = new \stdClass();

        $this->assertExpressionCompile(
            'hasPermission(object,"OWNER")',
            [
                'OWNER',
                $this->identicalTo($object),
            ],
            [
                'object' => $object,
            ]
        );
    }
}
