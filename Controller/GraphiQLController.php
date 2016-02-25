<?php

namespace Overblog\GraphQLBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GraphiQLController extends Controller
{
    public function indexAction()
    {
        return $this->render(
            $this->getParameter('overblog_graphql.graphiql_template'),
            [
                'endpoint' => $this->generateUrl('overblog_graphql_endpoint')
            ]
        );
    }
}
