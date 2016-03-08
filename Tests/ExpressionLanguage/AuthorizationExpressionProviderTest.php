<?php

namespace Overblog\GraphQLBundle\Tests\Error;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Tests\DIContainerMockTrait;

class AuthorizationExpressionProviderTest extends \PHPUnit_Framework_TestCase
{
    use DIContainerMockTrait;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    public function setUp()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function testHasRole()
    {
        $this->assertExpressionEvaluate('hasRole("ROLE_USER")', 'ROLE_USER');
    }

    public function testHasAnyRole()
    {
        $this->assertExpressionEvaluate('hasAnyRole(["ROLE_ADMIN", "ROLE_USER"])', 'ROLE_ADMIN');
    }

    public function testIsAnonymous()
    {
        $this->assertExpressionEvaluate('isAnonymous()', 'IS_AUTHENTICATED_ANONYMOUSLY');
    }

    public function testIsRememberMe()
    {
        $this->assertExpressionEvaluate('isRememberMe()', 'IS_AUTHENTICATED_REMEMBERED');
    }

    public function testIsFullyAuthenticated()
    {
        $this->assertExpressionEvaluate('isFullyAuthenticated()', 'IS_AUTHENTICATED_FULLY');
    }

    public function testIsAuthenticated()
    {
        $this->assertExpressionEvaluate('isAuthenticated()', $this->matchesRegularExpression('/IS_AUTHENTICATED_(REMEMBERED|FULLY)$/'));
    }

    public function testHasPermission()
    {
        $object = new \stdClass();

        $this->assertExpressionEvaluate(
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

        $this->assertExpressionEvaluate(
            'hasAnyPermission(object,["OWNER", "WRITER"])',
            [
                $this->matchesRegularExpression('/(OWNER|WRITER)$/'),
                $this->identicalTo($object),
            ],
            [
                'object' => $object,
            ]
        );
    }

    private function assertExpressionEvaluate($expression, $with, array $expressionValues = [],$expects = null, $return = true, $assertMethod = 'assertTrue')
    {
        $authChecker = $this->getAuthorizationCheckerIsGrantedWithExpectation($with, $expects, $return);

        $container = $this->getDIContainerMock(['security.authorization_checker' => $authChecker]);
        $this->expressionLanguage->setContainer($container);
        $this->$assertMethod($this->expressionLanguage->evaluate($expression, $expressionValues));
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
        $AuthorizationChecker = $this->getMockBuilder('Symfony\\Component\Security\\Core\Authorization\\AuthorizationCheckerInterface')
            ->disableOriginalConstructor()
            ->setMethods(['isGranted'])
            ->getMock()
        ;

        return $AuthorizationChecker;
    }
}
