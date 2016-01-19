<?php

namespace Overblog\GraphBundle\Request;

use GraphQL\Executor\Executor as GraphQLExecutor;
use GraphQL\Language\Parser as  GraphQLParser;
use GraphQL\Language\Source;
use GraphQL\Schema;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Overblog\GraphBundle\Event\Events;
use Overblog\GraphBundle\Event\ExecutorContextEvent;

class Executor
{
    private $schema;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(Schema $schema, EventDispatcherInterface $dispatcher)
    {
        $this->schema = $schema;
        $this->dispatcher = $dispatcher;
    }

    public function execute(array $data, array $context = [])
    {
        $source = new Source($data['query']);
        $ast = GraphQLParser::parse($source);

        $event = new ExecutorContextEvent($context);
        $this->dispatcher->dispatch(Events::EXECUTOR_CONTEXT, $event);

        return GraphQLExecutor::execute(
            $this->schema,
            $ast,
            $event->getExecutorContext(),
            $data['variables'],
            $data['operationName']
        );
    }
}
