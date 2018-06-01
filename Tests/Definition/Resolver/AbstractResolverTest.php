<?php

namespace Overblog\GraphQLBundle\Tests\Definition\Resolver;

use Overblog\GraphQLBundle\Definition\Resolver\AbstractResolver;
use Overblog\GraphQLBundle\Error\UserWarning;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

Class AbstractResolverTest extends TestCase
{

    public function testIsGranted()
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())->method('isGranted')->willReturn(true);

        /** @var AbstractResolver $controller */
        $controller = $this->getMockForAbstractClass(AbstractResolver::class);
        $controller->setAuthorizationChecker($authorizationChecker);

        $this->assertTrue($controller->isGranted('foo'));
    }

    /**
     * @expectedException \Overblog\GraphQLBundle\Error\UserWarning
     */
    public function testDenyAccessUnlessGranted()
    {
        $controller = $this->getMockBuilder(AbstractResolver::class)
            ->setMethods(array('isGranted'))
            ->getMock();
        $controller->expects($this->any())
            ->method('isGranted')
            ->willReturn(false);

        /** @var AbstractResolver $controller */
        $controller->denyAccessUnlessGranted('foo');
    }

}