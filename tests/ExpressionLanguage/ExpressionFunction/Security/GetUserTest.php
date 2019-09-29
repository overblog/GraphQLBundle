<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\GetUser;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

class GetUserTest extends TestCase
{
    protected function getFunctions()
    {
        $testUser = new User('testUser', 'testPassword');

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($testUser);

        return [new GetUser($security)];
    }

    public function testEvaluator(): void
    {
        $user = $this->expressionLanguage->evaluate('getUser()');
        $this->assertInstanceOf(UserInterface::class, $user);
    }

    public function testGetUserNoTokenStorage(): void
    {
        $globalVariable = new GlobalVariables(['container' => $this->getDIContainerMock()]);
        $globalVariable->has('container');
        $this->assertNull(eval($this->getCompileCode()));
    }

    public function testGetUserNoToken(): void
    {
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $globalVariable = new GlobalVariables(['container' => $this->getDIContainerMock(['security.token_storage' => $tokenStorage])]);
        $globalVariable->get('container');

        $this->getDIContainerMock(['security.token_storage' => $tokenStorage]);
        $this->assertNull(eval($this->getCompileCode()));
    }

    /**
     * @dataProvider getUserProvider
     *
     * @param $user
     * @param $expectedUser
     */
    public function testGetUser($user, $expectedUser): void
    {
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $globalVariable = new GlobalVariables(['container' => $this->getDIContainerMock(['security.token_storage' => $tokenStorage])]);
        $globalVariable->get('container');

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

    public function getUserProvider()
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $std = new \stdClass();

        return [
            [$user, $user],
            [$std, $std],
            [$token, $token],
            ['Anon.', null],
            [null, null],
            [10, null],
            [true, null],
        ];
    }

    private function getCompileCode()
    {
        return 'return '.$this->expressionLanguage->compile('getUser()').';';
    }
}
