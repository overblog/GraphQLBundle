<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Request;

use GraphQL\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Executor as GraphQLExecutor;
use GraphQL\GraphQL;
use GraphQL\Language\Parser as GraphQLParser;
use GraphQL\Language\Source;
use GraphQL\Schema;
use GraphQL\Validator\DocumentValidator;
use Overblog\GraphQLBundle\Error\ErrorHandler;
use Overblog\GraphQLBundle\Event\Events;
use Overblog\GraphQLBundle\Event\ExecutorContextEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Executor
{
    private $schema;

    /**
     * @var EventDispatcherInterface|null
     */
    private $dispatcher;

    /** @var bool */
    private $throwException;

    /** @var ErrorHandler|null */
    private $errorHandler;

    /** @var callable[] */
    private $validationRules;

    public function __construct(Schema $schema, EventDispatcherInterface $dispatcher = null, $throwException = false, ErrorHandler $errorHandler = null)
    {
        $this->schema = $schema;
        $this->dispatcher = $dispatcher;
        $this->throwException = (bool) $throwException;
        $this->errorHandler = $errorHandler;
        $this->validationRules = DocumentValidator::allRules();
    }

    public function addValidatorRule(callable $validatorRule)
    {
        $this->validationRules[] = $validatorRule;
    }

    /**
     * @param bool $throwException
     *
     * @return $this
     */
    public function setThrowException($throwException)
    {
        $this->throwException = (bool) $throwException;

        return $this;
    }

    public function execute(array $data, array $context = [])
    {
        if (null !== $this->dispatcher) {
            $event = new ExecutorContextEvent($context);
            $this->dispatcher->dispatch(Events::EXECUTOR_CONTEXT, $event);
            $context = $event->getExecutorContext();
        }

        $executionResult = $this->executeAndReturnResult(
            $this->schema,
            isset($data['query']) ? $data['query'] : null,
            $context,
            $data['variables'],
            $data['operationName']
        );

        if (null !== $this->errorHandler) {
            $this->errorHandler->handleErrors($executionResult, $this->throwException);
        }

        return $executionResult;
    }

    private function executeAndReturnResult(Schema $schema, $requestString, $rootValue = null, $variableValues = null, $operationName = null)
    {
        try {
            $source = new Source($requestString ?: '', 'GraphQL request');
            $documentAST = GraphQLParser::parse($source);
            $validationErrors = DocumentValidator::validate($schema, $documentAST, $this->validationRules);

            if (!empty($validationErrors)) {
                return new ExecutionResult(null, $validationErrors);
            }

            return GraphQLExecutor::execute($schema, $documentAST, $rootValue, $variableValues, $operationName);
        } catch (Error $e) {
            return new ExecutionResult(null, [$e]);
        }
    }
}
