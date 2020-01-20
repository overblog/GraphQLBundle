<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Controller;

use GraphQL\Utils\SchemaPrinter;
use Overblog\GraphQLBundle\Request\Executor as RequestExecutor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class ProfilerController
{
    private $profiler;
    private $twig;
    private $endpointUrl;
    private $requestExecutor;

    public function __construct(Profiler $profiler = null, Environment $twig = null, RouterInterface $router, RequestExecutor $requestExecutor)
    {
        $this->profiler = $profiler;
        $this->twig = $twig;
        $this->endpointUrl = $router->generate('overblog_graphql_endpoint');
        $this->requestExecutor = $requestExecutor;
    }

    public function __invoke(Request $request, $token)
    {
        if (null === $this->profiler) {
            throw new NotFoundHttpException('The profiler must be enabled.');
        }

        $this->profiler->disable();

        $profile = $this->profiler->loadProfile($token);

        $tokens = \array_map(function ($tokenData) {
            $profile = $this->profiler->loadProfile($tokenData['token']);
            $graphql = $profile ? $profile->getCollector('graphql') : null;
            $tokenData['graphql'] = $graphql;

            return $tokenData;
        }, $this->profiler->find(null, $this->endpointUrl, '100', null, null, null, null));

        $schemas = [];
        foreach ($this->requestExecutor->getSchemasNames() as $schemaName) {
            $schemas[$schemaName] = SchemaPrinter::doPrint($this->requestExecutor->getSchema($schemaName));
        }

        return new Response($this->twig->render('@OverblogGraphQL/profiler/graphql.html.twig', [
            'request' => $request,
            'profile' => $profile,
            'tokens' => $tokens,
            'token' => $token,
            'panel' => null,
            'schemas' => $schemas,
        ]), 200, ['Content-Type' => 'text/html']);
    }
}
