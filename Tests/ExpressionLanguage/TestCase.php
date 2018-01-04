<?php

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Tests\DIContainerMockTrait;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class TestCase extends BaseTestCase
{
    use DIContainerMockTrait;

    /** @var ExpressionLanguage */
    protected $expressionLanguage;

    public function setUp()
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $container = $this->getDIContainerMock();
        $this->expressionLanguage->setContainer($container);
        foreach ($this->getFunctions() as $function) {
            $this->expressionLanguage->addFunction($function);
        }
    }

    /**
     * @return ExpressionFunction[]
     */
    abstract protected function getFunctions();

    protected function assertExpressionCompile($expression, $with, array $expressionValues = [], $expects = null, $return = true, $assertMethod = 'assertTrue')
    {
        $expressionValues['container'] = $this->getDIContainerMock(['security.authorization_checker' => $this->getAuthorizationCheckerIsGrantedWithExpectation($with, $expects, $return)]);
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
