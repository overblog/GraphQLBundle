<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\ExpressionLanguage;

use Overblog\GraphQLBundle\Definition\GraphQLServices;
use Overblog\GraphQLBundle\ExpressionLanguage\ExpressionLanguage;
use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Overblog\GraphQLBundle\Resolver\MutationResolver;
use Overblog\GraphQLBundle\Resolver\QueryResolver;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Overblog\GraphQLBundle\Security\Security;
use Overblog\GraphQLBundle\Tests\DIContainerMockTrait;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use function array_keys;
use function call_user_func_array;
use function extract;
use function is_array;

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

    /**
     * @param Expression|string $expression
     * @param mixed             $with
     */
    protected function assertExpressionCompile(
        $expression,
        $with,
        array $vars = [],
        InvokedCount $expects = null,
        bool $return = true,
        string $assertMethod = 'assertTrue'
    ): void {
        $code = $this->expressionLanguage->compile($expression, array_keys($vars));
        ${TypeGenerator::GRAPHQL_SERVICES} = $this->createGraphQLServices([
            'security' => $this->getSecurityIsGrantedWithExpectation($with, $expects, $return),
        ]);
        ${TypeGenerator::GRAPHQL_SERVICES}->get('security');
        extract($vars);

        $this->$assertMethod(eval('return '.$code.';'));
    }

    /**
     * @param mixed                             $with
     * @param InvokedCount|AnyInvokedCount|null $expects
     * @param mixed                             $return
     */
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

        // @phpstan-ignore-next-line
        $methodExpectation = $security
            ->expects($expects)
            ->method('isGranted');

        call_user_func_array([$methodExpectation, 'with'], is_array($with) ? $with : [$with]);

        $methodExpectation->will($returnValue);

        return new Security($security);
    }

    private function getCoreSecurityMock(): CoreSecurity
    {
        return $this->getMockBuilder(CoreSecurity::class)
            ->disableOriginalConstructor()
            ->setMethods(['isGranted'])
            ->getMock();
    }

    protected function createGraphQLServices(array $services = []): GraphQLServices
    {
        return new GraphQLServices(
            $this->createMock(TypeResolver::class),
            $this->createMock(QueryResolver::class),
            $this->createMock(MutationResolver::class),
            $services
        );
    }
}
