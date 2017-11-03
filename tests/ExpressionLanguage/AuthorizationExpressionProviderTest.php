<?php

namespace Overblog\GraphQLBundle\Tests\Error;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Tests\DIContainerMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AuthorizationExpressionProviderTest extends TestCase
{
    use DIContainerMockTrait;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    public function setUp()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function testHasRole()
    {
        $this->assertExpressionCompile('hasRole("ROLE_USER")', 'ROLE_USER');
    }

    public function testHasAnyRole()
    {
        $this->assertExpressionCompile('hasAnyRole(["ROLE_ADMIN", "ROLE_USER"])', 'ROLE_ADMIN');

        $this->assertExpressionCompile(
            'hasAnyRole(["ROLE_ADMIN", "ROLE_USER"])',
            $this->matchesRegularExpression('/^ROLE_(USER|ADMIN)$/'),
            [],
            $this->exactly(2),
            false,
            'assertFalse'
        );
    }

    public function testIsAnonymous()
    {
        $this->assertExpressionCompile('isAnonymous()', 'IS_AUTHENTICATED_ANONYMOUSLY');
    }

    public function testIsRememberMe()
    {
        $this->assertExpressionCompile('isRememberMe()', 'IS_AUTHENTICATED_REMEMBERED');
    }

    public function testIsFullyAuthenticated()
    {
        $this->assertExpressionCompile('isFullyAuthenticated()', 'IS_AUTHENTICATED_FULLY');
    }

    public function testIsAuthenticated()
    {
        $this->assertExpressionCompile('isAuthenticated()', $this->matchesRegularExpression('/^IS_AUTHENTICATED_(REMEMBERED|FULLY)$/'));
    }

    public function testHasPermission()
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

    public function testHasAnyPermission()
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

    private function assertExpressionCompile($expression, $with, array $expressionValues = [], $expects = null, $return = true, $assertMethod = 'assertTrue')
    {
        $authChecker = $this->getAuthorizationCheckerIsGrantedWithExpectation($with, $expects, $return);

        $container = $this->getDIContainerMock(['security.authorization_checker' => $authChecker]);
        $this->expressionLanguage->setContainer($container);

        extract($expressionValues);

        $code = $this->expressionLanguage->compile($expression, array_keys($expressionValues));

        $this->$assertMethod(eval('return '.$code.';'));
    }

    private function getAuthorizationCheckerIsGrantedWithExpectation($with, $expects = null, $return = true)
    {
        if (null === $expects) {
            $expects = $this->once();
        }
        $authChecker = $this->getAuthorizationCheckerMock();

        if ($return instanceof \PHPUnit_Framework_MockObject_Stub_Return) {
            $returnValue = $return;
        } else {
            $returnValue = $this->returnValue($return);
        }

        $methodExpectation = $authChecker
            ->expects($expects)
            ->method('isGranted');

        call_user_func_array([$methodExpectation, 'with'], is_array($with) ? $with : [$with]);

        $methodExpectation->will($returnValue);

        return $authChecker;
    }

    private function getAuthorizationCheckerMock()
    {
        $AuthorizationChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isGranted'])
            ->getMock()
        ;

        return $AuthorizationChecker;
    }
}
