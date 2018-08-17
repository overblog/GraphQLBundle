<?php

namespace Overblog\GraphQLBundle\EventListener;

use Overblog\GraphQLBundle\Event\ExecutorContextEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestFilesListener
{
    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function onExecutorContextEvent(ExecutorContextEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return;
        }

        $context = $event->getExecutorContext();
        $context['request_files'] = $request->files;
    }
}
