<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Controller;

use GraphQL\Utils\SchemaPrinter;
use Overblog\GraphQLBundle\Request\Executor as RequestExecutor;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function array_map;

class ProfilerController
{
    private ?Profiler $profiler;
    private ?Environment $twig;
    private string $endpointUrl;
    private RequestExecutor $requestExecutor;
    private ?string $queryMatch;

    public function __construct(?Profiler $profiler, ?Environment $twig, RouterInterface $router, RequestExecutor $requestExecutor, ?string $queryMatch)
    {
        $this->profiler = $profiler;
        $this->twig = $twig;
        $this->endpointUrl = $router->generate('overblog_graphql_endpoint');
        $this->requestExecutor = $requestExecutor;
        $this->queryMatch = $queryMatch;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(Request $request, string $token): Response
    {
        if (null === $this->profiler) {
            throw new ServiceNotFoundException('The profiler must be enabled.');
        }

        if (null === $this->twig) {
            throw new ServiceNotFoundException('The GraphQL Profiler require twig');
        }

        $this->profiler->disable();

        $profile = $this->profiler->loadProfile($token);

        $tokens = array_map(function ($tokenData) {
            $profile = $this->profiler->loadProfile($tokenData['token']);
            $graphql = $profile ? $profile->getCollector('graphql') : null;
            $tokenData['graphql'] = $graphql;

            return $tokenData;
        }, $this->profiler->find(null, $this->queryMatch ?: $this->endpointUrl, '100', 'POST', null, null, null)); // @phpstan-ignore-line

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
