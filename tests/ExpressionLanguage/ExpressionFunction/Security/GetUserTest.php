<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\GetUser;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

class GetUserTest extends TestCase
{
    protected function getFunctions()
    {
        return [new GetUser()];
    }

    public function testEvaluator(): void
    {
        $testUser = new User('testUser', 'testPassword');
        $coreSecurity = $this->createMock(CoreSecurity::class);
        $coreSecurity->method('getUser')->willReturn($testUser);
        $globalVars = new GlobalVariables(['security' => new Security($coreSecurity)]);

        $user = $this->expressionLanguage->evaluate('getUser()', [TypeGenerator::GLOBAL_VARS => $globalVars]);
        $this->assertInstanceOf(UserInterface::class, $user);
    }

    public function testGetUserNoTokenStorage(): void
    {
        ${TypeGenerator::GLOBAL_VARS} = new GlobalVariables(['security' => new Security($this->createMock(CoreSecurity::class))]);
        ${TypeGenerator::GLOBAL_VARS}->get('security');
        $this->assertNull(eval($this->getCompileCode()));
    }

    public function testGetUserNoToken(): void
    {
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        ${TypeGenerator::GLOBAL_VARS} = new GlobalVariables(
            [
                'security' => new Security(
                    new CoreSecurity(
                        $this->getDIContainerMock(['security.token_storage' => $tokenStorage])
                    )
                ),
            ]
        );
        ${TypeGenerator::GLOBAL_VARS}->get('security');

        $this->getDIContainerMock(['security.token_storage' => $tokenStorage]);
        $this->assertNull(eval($this->getCompileCode()));
    }

    /**
     * @dataProvider getUserProvider
     *
     * @param mixed $user
     * @param mixed $expectedUser
     */
    public function testGetUser($user, $expectedUser): void
    {
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        ${TypeGenerator::GLOBAL_VARS} = new GlobalVariables(
            [
                'security' => new Security(
                    new CoreSecurity(
                        $this->getDIContainerMock(['security.token_storage' => $tokenStorage])
                    )
                ),
            ]
        );
        ${TypeGenerator::GLOBAL_VARS}->get('security');

        $token
            ->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->assertSame($expectedUser, eval($this->getCompileCode()));
    }

    public function getUserProvider(): array
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();

        return [
            [$user, $user],
            ['Anon.', null],
            [null, null],
            [10, null],
            [true, null],
        ];
    }

    private function getCompileCode(): string
    {
        return 'return '.$this->expressionLanguage->compile('getUser()').';';
    }
}
