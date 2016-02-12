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

    /** @var boolean */
    private $enabledDebug;

    public function __construct(Schema $schema, EventDispatcherInterface $dispatcher, $enabledDebug)
    {
        $this->schema = $schema;
        $this->dispatcher = $dispatcher;
        $this->enabledDebug = $enabledDebug;
    }

    /**
     * @return boolean
     */
    public function getEnabledDebug()
    {
        return $this->enabledDebug;
    }

    /**
     * @param boolean $enabledDebug
     * @return $this
     */
    public function setEnabledDebug($enabledDebug)
    {
        $this->enabledDebug = $enabledDebug;

        return $this;
    }

    public function execute(array $data, array $context = [])
    {
        $source = new Source($data['query']);
        $ast = GraphQLParser::parse($source);

        $event = new ExecutorContextEvent($context);
        $this->dispatcher->dispatch(Events::EXECUTOR_CONTEXT, $event);

        $executionResult = GraphQLExecutor::execute(
            $this->schema,
            $ast,
            $event->getExecutorContext(),
            $data['variables'],
            $data['operationName']
        );

        if ($this->enabledDebug && !empty($executionResult->errors)) {
            foreach($executionResult->errors as $error) {
                // if is a try catch exception wrapped in Error
                if ($error->getPrevious() instanceof \Exception) {
                    throw $executionResult->errors[0]->getPrevious();
                }
            }
        }

        return $executionResult;
    }
}
