<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Tests\DIContainerMockTrait;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class TestCase extends BaseTestCase
{
    use DIContainerMockTrait;

    /** @var ExpressionLanguage */
    protected $expressionLanguage;

    public function setUp(): void
    {
        $this->expressionLanguage = new ExpressionLanguage();
        foreach ($this->getFunctions() as $function) {
            $this->expressionLanguage->addFunction($function);
        }
    }

    /**
     * @return ExpressionFunction[]
     */
    abstract protected function getFunctions();

    protected function assertExpressionCompile($expression, $with, array $vars = [], $expects = null, $return = true, $assertMethod = 'assertTrue'): void
    {
        $code = $this->expressionLanguage->compile($expression, \array_keys($vars));
        $globalVariable = new GlobalVariables([
            'container' => $this->getDIContainerMock(
                ['security.authorization_checker' => $this->getAuthorizationCheckerIsGrantedWithExpectation($with, $expects, $return)]
            ),
        ]);
        $globalVariable->get('container');
        \extract($vars);

        $this->$assertMethod(eval('return '.$code.';'));
    }

    protected function getAuthorizationCheckerIsGrantedWithExpectation($with, $expects = null, $return = true)
    {
        if (null === $expects) {
            $expects = $this->once();
        }
        $authChecker = $this->getAuthorizationCheckerMock();

        if ($return instanceof Stub) {
            $returnValue = $return;
        } else {
            $returnValue = $this->returnValue($return);
        }

        $methodExpectation = $authChecker
            ->expects($expects)
            ->method('isGranted');

        \call_user_func_array([$methodExpectation, 'with'], \is_array($with) ? $with : [$with]);

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
