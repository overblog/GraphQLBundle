<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Overblog\GraphQLBundle\Definition\GlobalVariables;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Security\Security;
use Overblog\GraphQLBundle\Tests\DIContainerMockTrait;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Security as CoreSecurity;

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
        ${TypeGenerator::GLOBAL_VARS} = new GlobalVariables([
            'security' => $this->getSecurityIsGrantedWithExpectation($with, $expects, $return),
        ]);
        ${TypeGenerator::GLOBAL_VARS}->get('security');
        \extract($vars);

        $this->$assertMethod(eval('return '.$code.';'));
    }

    protected function getSecurityIsGrantedWithExpectation($with, $expects = null, $return = true): Security
    {
        if (null === $expects) {
            $expects = $this->once();
        }
        $security = $this->getCoreSecurityMock();

        if ($return instanceof Stub) {
            $returnValue = $return;
        } else {
            $returnValue = $this->returnValue($return);
        }

        $methodExpectation = $security
            ->expects($expects)
            ->method('isGranted');

        \call_user_func_array([$methodExpectation, 'with'], \is_array($with) ? $with : [$with]);

        $methodExpectation->will($returnValue);

        return new Security($security);
    }

    private function getCoreSecurityMock()
    {
        return $this->getMockBuilder(CoreSecurity::class)
            ->disableOriginalConstructor()
            ->setMethods(['isGranted'])
            ->getMock()
            ;
    }
}
