<?php

namespace Overblog\GraphQLBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GraphiQLController extends Controller
{
    public function indexAction($schemaName = null)
    {
        if (null === $schemaName) {
            $endpoint = $this->generateUrl('overblog_graphql_endpoint');
        } else {
            $endpoint = $this->generateUrl('overblog_graphql_multiple_endpoint', ['schemaName' => $schemaName]);
        }

        return $this->render(
            $this->getParameter('overblog_graphql.graphiql_template'),
            [
                'endpoint' => $endpoint,
                'versions' => [
                    'graphiql' => $this->getParameter('overblog_graphql.versions.graphiql'),
                    'react' => $this->getParameter('overblog_graphql.versions.react'),
                    'fetch' => $this->getParameter('overblog_graphql.versions.fetch'),
                ],
            ]
        );
    }
}
