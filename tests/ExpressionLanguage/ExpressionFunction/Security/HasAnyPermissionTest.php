<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\HasAnyPermission;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;

class HasAnyPermissionTest extends TestCase
{
    protected function getFunctions()
    {
        return [new HasAnyPermission()];
    }

    public function testHasAnyPermission(): void
    {
        $object = new \stdClass();

        $this->assertExpressionCompile(
            'hasAnyPermission(object,["OWNER", "WRITER"])',
            [
                $this->matchesRegularExpression('/^(OWNER|WRITER)$/'),
                $this->identicalTo($object),
            ],
            [
                'object' => $object,
            ]
        );

        $this->assertExpressionCompile(
            'hasAnyPermission(object,["OWNER", "WRITER"])',
            [
                $this->matchesRegularExpression('/^(OWNER|WRITER)$/'),
                $this->identicalTo($object),
            ],
            [
                'object' => $object,
            ],
            $this->exactly(2),
            false,
            'assertFalse'
        );
    }
}
