<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Executor;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;

class Executor implements ExecutorInterface
{
    /**
     * @var PromiseAdapter
     */
    private $promiseAdapter;

    /**
     * @param Schema      $schema
     * @param string      $requestString
     * @param null|array  $rootValue
     * @param null|array  $contextValue
     * @param null|array  $variableValues
     * @param null|string $operationName
     *
     * @return ExecutionResult|Promise
     */
    public function execute(Schema $schema, $requestString, $rootValue = null, $contextValue = null, $variableValues = null, $operationName = null)
    {
        $args = func_get_args();

        if (null === $this->promiseAdapter) {
            $method = 'executeQuery';
        } else {
            array_unshift($args, $this->promiseAdapter);
            $method = 'promiseToExecute';
        }

        return call_user_func_array(sprintf('\%s::%s', GraphQL::class, $method), $args);
    }

    /**
     * @param PromiseAdapter|null $promiseAdapter
     */
    public function setPromiseAdapter(PromiseAdapter $promiseAdapter = null)
    {
        $this->promiseAdapter = $promiseAdapter;
    }
}
