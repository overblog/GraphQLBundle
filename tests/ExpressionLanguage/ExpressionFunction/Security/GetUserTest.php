<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage\ExpressionFunction\Security;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionFunction\Security\GetUser;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;
use Overblog\GraphQLBundle\Tests\ExpressionLanguage\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

final class GetUserTest extends TestCase
{
    protected function getFunctions()
    {
        return [new GetUser()];
    }

    public function testEvaluator(): void
    {
        if (class_exists(InMemoryUser::class)) {
            $testUser = new InMemoryUser('testUser', 'testPassword');
        } else {
            $testUser = new User('testUser', 'testPassword');
        }
        $coreSecurity = $this->createMock(CoreSecurity::class);
        $coreSecurity->method('getUser')->willReturn($testUser);
        $services = $this->createGraphQLServices([Security::class => new Security($coreSecurity)]);

        $user = $this->expressionLanguage->evaluate('getUser()', [TypeGenerator::GRAPHQL_SERVICES => $services]);
        $this->assertInstanceOf(UserInterface::class, $user);
    }

    public function testGetUserNoTokenStorage(): void
    {
        ${TypeGenerator::GRAPHQL_SERVICES} = $this->createGraphQLServices(
            [Security::class => new Security($this->createMock(CoreSecurity::class))]
        );
        $this->assertNull(eval($this->getCompileCode()));
    }

    public function testGetUserNoToken(): void
    {
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        ${TypeGenerator::GRAPHQL_SERVICES} = $this->createGraphQLServices(
            [
                Security::class => new Security(
                    new CoreSecurity(
                        $this->getDIContainerMock(['security.token_storage' => $tokenStorage])
                    )
                ),
            ]
        );

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

        ${TypeGenerator::GRAPHQL_SERVICES} = $this->createGraphQLServices(
            [
                Security::class => new Security(
                    new CoreSecurity(
                        $this->getDIContainerMock(['security.token_storage' => $tokenStorage])
                    )
                ),
            ]
        );

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
            [null, null],
        ];
    }

    private function getCompileCode(): string
    {
        return 'return '.$this->expressionLanguage->compile('getUser()').';';
    }
}
