<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Controller;

use GraphQL\Type\Schema;
use Overblog\GraphQLBundle\Controller\ProfilerController;
use Overblog\GraphQLBundle\Request\Executor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Router;
use Twig\Environment;

class ProfilerControllerTest extends TestCase
{
    /**
     * @return Router&MockObject
     */
    protected function getMockRouter(): Router
    {
        $router = $this->getMockBuilder(Router::class)->disableOriginalConstructor()->setMethods(['generate'])->getMock();
        $router->expects($this->once())->method('generate')->willReturn('/endpoint');

        return $router;
    }

    /**
     * @return Executor&MockObject
     */
    protected function getMockExecutor(bool $expected = true): Executor
    {
        $executor = $this->getMockBuilder(Executor::class)->disableOriginalConstructor()->setMethods(['getSchemasNames', 'getSchema'])->getMock();
        if ($expected) {
            $schema = new Schema([]);
            $executor->expects($this->once())->method('getSchemasNames')->willReturn(['schema']);
            $executor->expects($this->once())->method('getSchema')->willReturn($schema);
        }

        return $executor;
    }

    /**
     * @return Profiler&MockObject
     */
    protected function getMockProfiler(): Profiler
    {
        return $this->getMockBuilder(Profiler::class)
            ->disableOriginalConstructor()
            ->setMethods(['disable', 'loadProfile', 'find'])->getMock();
    }

    public function testInvokeWithoutProfiler(): void
    {
        $controller = new ProfilerController(null, null, $this->getMockRouter(), $this->getMockExecutor(false), null);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('The profiler must be enabled.');
        $controller->__invoke(new Request(), 'token');
    }

    public function testInvokeWithoutTwig(): void
    {
        $controller = new ProfilerController($this->getMockProfiler(), null, $this->getMockRouter(), $this->getMockExecutor(false), null);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('The GraphQL Profiler require twig');
        $controller->__invoke(new Request(), 'token');
    }

    public function testWithToken(): void
    {
        $profilerMock = $this->getMockProfiler();
        $executorMock = $this->getMockExecutor();
        $routerMock = $this->getMockRouter();
        $twigMock = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->setMethods(['render'])->getMock();
        $controller = new ProfilerController($profilerMock, $twigMock, $routerMock, $executorMock, null);
        $graphqlData = ['graphql_data'];

        /** @var MockObject $profilerMock */
        $profilerMock->expects($this->once())->method('disable');
        $profilerMock->expects($this->once())->method('find')->willReturn([['token' => 'token']]);
        $profileMock = $this->getMockBuilder(Profile::class)->disableOriginalConstructor()->setMethods(['getCollector'])->getMock();
        $profileMock->expects($this->once())->method('getCollector')->willReturn($graphqlData);

        $profilerMock->expects($this->exactly(2))->method('loadProfile')->willReturn($profileMock);

        $request = new Request();
        $twigMock->expects($this->once())->method('render')->with('@OverblogGraphQL/profiler/graphql.html.twig', [
            'request' => $request,
            'profile' => $profileMock,
            'tokens' => [['token' => 'token', 'graphql' => $graphqlData]],
            'token' => 'token',
            'panel' => null,
            'schemas' => ['schema' => "\n"],
        ]);

        $controller->__invoke($request, 'token');
    }
}
